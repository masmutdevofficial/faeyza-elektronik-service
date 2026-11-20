"""Integrity checks for the tested full ZIP and the six-file hosting update."""
from pathlib import Path
import hashlib,json,secrets,subprocess,zipfile

ROOT=Path(__file__).resolve().parents[1];OUT=ROOT/'output/delivery';results=[]
def check(name,ok):
    results.append({'name':name,'status':'PASS' if ok else 'FAIL'});print(('PASS ' if ok else 'FAIL ')+name)
    if not ok:raise AssertionError(name)
def digest(data):return hashlib.sha256(data).hexdigest()
full_path=OUT/'faeyza-elektronik-service.zip';patch_path=OUT/'faeyza-perbaikan-ringkasan-stok-20261120.zip'
for path in [full_path,patch_path]:
    check(path.name+' SHA256 matches',digest(path.read_bytes())==path.with_suffix('.zip.sha256').read_text().split()[0])
with zipfile.ZipFile(full_path) as full,zipfile.ZipFile(patch_path) as patch:
    check('Both archives pass CRC',full.testzip() is None and patch.testzip() is None)
    prefix='faeyza-elektronik-service/';manifest=json.loads(full.read(prefix+'MANIFEST.json'));update=json.loads(patch.read('MANIFEST-UPDATE.json'))
    check('Full manifest matches all archive entries',len(full.namelist())==len(manifest['files'])+1 and all(digest(full.read(prefix+name))==value for name,value in manifest['files'].items()))
    check('Full package has current release date and update guide',manifest['release_date']=='2026-11-20' and prefix+'docs/PERBAIKAN-RINGKASAN-STOK.md' in full.namelist())
    check('Update includes exactly six runtime files and two instructions',len(update['files'])==6 and set(patch.namelist())==set(update['files'])|{'PANDUAN-UPDATE.md','MANIFEST-UPDATE.json'})
    check('Update paths are bounded to application sources',all(name.startswith(('app/','views/')) and '..' not in Path(name).parts for name in update['files']))
    check('Update requires no database migration',update['schema_migration_required'] is False and not any(name.endswith('.sql') for name in patch.namelist()))
    check('Update cannot overwrite env, routing or branding',not any(set(Path(name).parts)&{'.env','.env.testing','.htaccess','config','public','database','storage'} for name in patch.namelist()))
    check('Update matches tested working source and full package',all(digest(patch.read(name))==values['after_sha256']==digest((ROOT/name).read_bytes())==manifest['files'][name] for name,values in update['files'].items()))
    check('Old file fingerprints match committed baseline',all(digest(subprocess.check_output(['git','show',f"{update['baseline_commit']}:{name}"],cwd=ROOT))==values['before_sha256'] for name,values in update['files'].items()))
    target=ROOT/'tests/artifacts'/('patch-apply-'+secrets.token_hex(4));target.mkdir(parents=True)
    for name in update['files']:
        file=target/name;file.parent.mkdir(parents=True,exist_ok=True);file.write_bytes(subprocess.check_output(['git','show',f"{update['baseline_commit']}:{name}"],cwd=ROOT))
    protected={'.env':b'PRIVATE_CONFIGURATION_NOT_TO_BE_CHANGED','.htaccess':b'ORIGINAL_ROUTING','config/branding.php':b'ORIGINAL_BRANDING','database/placeholder.txt':b'ORIGINAL_DATA'}
    for name,data in protected.items():file=target/name;file.parent.mkdir(parents=True,exist_ok=True);file.write_bytes(data)
    patch.extractall(target)
    check('Applying ZIP replaces all six files correctly',all((target/name).read_bytes()==(ROOT/name).read_bytes() for name in update['files']))
    check('Applying ZIP preserves configuration and data files',all((target/name).read_bytes()==data for name,data in protected.items()))
    tested=json.loads((ROOT/'tests/artifacts/delivery-package-results.json').read_text())
    tested_manifest=json.loads((Path(tested['run_directory'])/'verified-manifest.json').read_text())
    check('Final full ZIP is byte-identical to the package installation tested',tested['pass']==37 and tested['fail']==0 and tested_manifest==manifest)
summary={'pass':len(results),'fail':0,'results':results,'update_sha256':digest(patch_path.read_bytes()),'full_sha256':digest(full_path.read_bytes())}
(ROOT/'tests/artifacts/summary-stock-package-results.json').write_text(json.dumps(summary,indent=2),encoding='utf-8')
print(json.dumps({'pass':len(results),'fail':0}))
