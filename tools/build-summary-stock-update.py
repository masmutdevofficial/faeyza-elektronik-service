"""Six-file, non-destructive update for an existing Faeyza installation."""
import hashlib,json,subprocess,zipfile
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1]
OUT=ROOT/'output/delivery';OUT.mkdir(parents=True,exist_ok=True)
paths=['app/Domain.php','app/controller.php','views/part.php','views/parts.php','views/part-form.php','views/record-delete.php']
base=subprocess.check_output(['git','rev-parse','HEAD'],cwd=ROOT,text=True).strip()
entries={name:(ROOT/name).read_bytes() for name in paths}
manifest={'application':'Faeyza Elektronik Service','release_date':'2026-11-20','baseline_commit':base,'schema_migration_required':False,'files':{}}
for name,data in entries.items():
    before=subprocess.check_output(['git','show',f'{base}:{name}'],cwd=ROOT)
    manifest['files'][name]={'before_sha256':hashlib.sha256(before).hexdigest(),'after_sha256':hashlib.sha256(data).hexdigest()}
entries['PANDUAN-UPDATE.md']=(ROOT/'docs/PERBAIKAN-RINGKASAN-STOK.md').read_bytes()
entries['MANIFEST-UPDATE.json']=json.dumps(manifest,indent=2).encode('utf-8')
path=OUT/'faeyza-perbaikan-ringkasan-stok-20261120.zip'
with zipfile.ZipFile(path,'w',compression=zipfile.ZIP_DEFLATED,compresslevel=9) as archive:
    for name,data in sorted(entries.items()):
        info=zipfile.ZipInfo(name,date_time=(2026,11,20,12,0,0));info.compress_type=zipfile.ZIP_DEFLATED;info.external_attr=0o100644<<16
        archive.writestr(info,data)
digest=hashlib.sha256(path.read_bytes()).hexdigest()
path.with_suffix('.zip.sha256').write_text(digest+'  '+path.name+'\n',encoding='ascii')
print(json.dumps({'archive':str(path),'files':len(entries),'bytes':path.stat().st_size,'sha256':digest}))
