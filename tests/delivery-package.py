"""Verify the delivered ZIP using a new disposable local database, never production."""
from pathlib import Path
import hashlib, http.cookiejar, json, os, re, secrets, shutil, subprocess, time, urllib.error, urllib.parse, urllib.request, zipfile

ROOT=Path(__file__).resolve().parents[1]
PHP=Path('C:/project/bengkel-runtime/php/php.exe')
ZIP=ROOT/'output/delivery/faeyza-elektronik-service.zip'
RUN=ROOT/'tests/artifacts'/('delivery-'+secrets.token_hex(4))
RUN.mkdir(parents=True)
results=[]; servers=[]; created=False
DB='faeyza_delivery_20261118_'+secrets.token_hex(4)+'_test'
creds=json.loads(Path('C:/project/bengkel-runtime/local-database-admin.json').read_text(encoding='utf-8-sig'))
env={k:v for k,v in os.environ.items() if k not in ['APP_ENV','DB_HOST','DB_PORT','DB_NAME','DB_USER','DB_PASSWORD','SESSION_SECURE']}
def check(name,ok):
    results.append({'name':name,'status':'PASS' if ok else 'FAIL'})
    print(('PASS ' if ok else 'FAIL ')+name,flush=True)
    if not ok: raise RuntimeError(name)
def php(app,*args,input=None,expect=0):
    r=subprocess.run([str(PHP),*args],cwd=app,env=env,input=input,text=True,capture_output=True)
    if expect is not None and r.returncode!=expect: raise RuntimeError('PHP command failed; private output withheld')
    return r
class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self,*args): return None
class Client:
    def __init__(self,base):
        self.base=base;self.opener=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()),NoRedirect())
    def request(self,page=None,data=None,params=None,path=None):
        route=path if path is not None else ('index.php?'+urllib.parse.urlencode({'page':page,**(params or {})}))
        url=self.base+route
        body=None if data is None else urllib.parse.urlencode(data,doseq=True).encode()
        try: response=self.opener.open(urllib.request.Request(url,body),timeout=15)
        except urllib.error.HTTPError as error: response=error
        return response.status,response.read().decode('utf-8',errors='replace'),response.headers
    def token(self,page=None,params=None,path=None):
        status,body,_=self.request(page,params=params,path=path)
        assert status==200
        return re.search(r'name="csrf" value="([^"]+)"',body)[1]
def server(app,port,docroot,router=None):
    log=(RUN/f'server-{port}.log').open('w',encoding='utf-8')
    command=[str(PHP),'-S',f'127.0.0.1:{port}','-t',str(docroot)]
    if router: command.append(str(router))
    process=subprocess.Popen(command,cwd=app,env=env,stdout=log,stderr=log,creationflags=subprocess.CREATE_NO_WINDOW)
    servers.append((process,log));
    for _ in range(40):
        if process.poll() is not None: raise RuntimeError('Isolated HTTP server stopped')
        try:
            urllib.request.urlopen(f'http://127.0.0.1:{port}/',timeout=1);break
        except urllib.error.HTTPError: break
        except urllib.error.URLError: time.sleep(.1)
    return f'http://127.0.0.1:{port}/'

try:
    with zipfile.ZipFile(ZIP) as archive:
        check('ZIP CRC valid',archive.testzip() is None)
        names=archive.namelist();prefix='faeyza-elektronik-service/'
        check('Archive paths stay within one application folder',all(name.startswith(prefix) and '..' not in Path(name).parts and not Path(name).is_absolute() for name in names))
        forbidden=['.env','.env.testing','.git','tests','output','node_modules','local-credentials.json','local-fixtures.json']
        check('No private config, fixtures or repository history',not any(set(Path(name).parts)&set(forbidden) for name in names))
        check('Storage contains only empty placeholders',all(name.endswith('/.gitkeep') for name in names if '/storage/' in name))
        manifest=json.loads(archive.read(prefix+'MANIFEST.json'))
        check('All manifest file hashes match ZIP content',all(hashlib.sha256(archive.read(prefix+name)).hexdigest()==digest for name,digest in manifest['files'].items()))
        check('Official assets and MDI license included',all(prefix+name in names for name in ['config/assets/faeyza-ttd.png','public/assets/faeyza-logo.jpg','public/assets/icons/mdi/LICENSE']))
        check('Guide, schema and both admin setup commands included',all(prefix+name in names for name in ['PANDUAN-INSTALASI.pdf','docs/PANDUAN-INSTALASI.md','database/schema.sql','bin/create-admin.php','bin/admin-sql.php','deploy/shared-hosting/index.php.example']))
        secret_values=[]
        for secret_file in [ROOT/'.env',ROOT/'.env.testing']:
            for line in secret_file.read_text(encoding='utf-8').splitlines():
                if line.startswith('DB_PASSWORD='):
                    value=line.split('=',1)[1].strip().strip('"\'')
                    if len(value)>=8: secret_values.append(value.encode())
        if len(creds.get('password',''))>=8:secret_values.append(creds['password'].encode())
        check('Known local database secrets absent',not any(secret in archive.read(name) for secret in secret_values for name in names))
        archive.extractall(RUN)
    app=RUN/'faeyza-elektronik-service'
    check('Every packaged PHP file passes syntax lint',all(php(app,'-l',str(file)).returncode==0 for file in app.rglob('*.php')))
    config={'APP_ENV':'development','APP_URL':'http://127.0.0.1:8082','DB_HOST':creds.get('host','127.0.0.1'),'DB_PORT':str(creds.get('port',3307)),'DB_NAME':DB,'DB_USER':creds['user'],'DB_PASSWORD':creds['password'],'SESSION_SECURE':'false'}
    (app/'.env').write_text(''.join(f'{key}={value}\n' for key,value in config.items()),encoding='utf-8')
    guard='require "app/bootstrap.php"; $n=getenv("DB_NAME"); if(!preg_match("/^faeyza_delivery_20261118_[a-f0-9]{8}_test$/D",$n))exit(2); '
    php(app,'-r',guard+'Database::connection(false)->query("CREATE DATABASE `".$n."` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");')
    created=True
    check('Fresh schema setup succeeds on isolated new database',php(app,'bin/setup.php').returncode==0)
    check('Repeated setup preserves schema and succeeds',php(app,'bin/setup.php').returncode==0)
    check('Doctor validates extracted installation',php(app,'bin/doctor.php').returncode==0)
    admin_phone='081290001111';password=secrets.token_hex(3)
    check('Admin can be created with a six-character password',php(app,'bin/create-admin.php',admin_phone,input=password+'\n').returncode==0)
    sql=php(app,'bin/admin-sql.php','081290002222',input=password+'\n').stdout
    check('Offline admin helper emits SQL without plaintext password','INSERT INTO users' in sql and password not in sql)
    php(app,'-r','require "app/bootstrap.php"; Database::connection()->query(stream_get_contents(STDIN));',input=sql)
    check('Offline admin SQL imports successfully',True)
    duplicate=php(app,'-r','require "app/bootstrap.php"; try{Database::connection()->query(stream_get_contents(STDIN));}catch(mysqli_sql_exception $e){exit($e->getCode()===1062?0:2);}exit(1);',input=sql)
    check('Duplicate admin SQL cannot overwrite an existing account',duplicate.returncode==0)
    invalid=php(app,'bin/admin-sql.php',admin_phone,input='12345\n',expect=None)
    check('Offline helper rejects fewer than six characters',invalid.returncode!=0 and 'INSERT INTO' not in invalid.stdout)
    invalid_phone=php(app,'bin/admin-sql.php',"' OR 1=1",input=password+'\n',expect=None)
    check('Offline helper rejects malicious phone input',invalid_phone.returncode!=0 and 'INSERT INTO' not in invalid_phone.stdout)
    base=server(app,8082,app/'public',app/'public/router.php')
    admin=Client(base);consumer=Client(base)
    csrf=admin.token(path='admin/')
    status,_,headers=admin.request(path='admin/',data={'csrf':csrf,'phone':admin_phone,'password':password})
    check('Packaged admin login works',status==303 and 'page=dashboard' in headers.get('Location',''))
    status,body,_=admin.request('dashboard');check('Grouped summary exists in extracted package',status==200 and 'status=working' in body and 'status=done' in body)
    status,_,_=consumer.request('register',data={'csrf':consumer.token('register'),'name':'Pelanggan Paket Uji','address':'Alamat pengujian lokal','phone':'081290003333','password':password,'password_confirm':password})
    check('Consumer registration works on clean database',status==303)
    status,form,_=consumer.request('service-new');submission=re.search(r'name="submission_key" value="([^"]+)"',form)[1]
    today=php(app,'-r','require "app/bootstrap.php";echo Clock::today();').stdout
    payload={'csrf':consumer.token('service-new'),'submission_key':submission,'received_date':today,'customer_name':'Pelanggan Paket Uji','address':'Alamat pengujian lokal','contact_phone':'081290003333','item_name':'TV','brand':'SONY','model':'Model paket uji','complaint':'Pengujian paket instalasi','location':'Bengkel','accessories[]':['Kabel Power','Remote']}
    status,_,headers=consumer.request('service-new',data=payload)
    check('Consumer can submit a service in extracted package',status==303 and 'page=success' in headers.get('Location',''))
    order_id=urllib.parse.parse_qs(urllib.parse.urlparse(headers['Location']).query)['id'][0]
    status,invoice,_=consumer.request('invoice',params={'id':order_id})
    check('Invoice issued with server date and first sequence',status==200 and 'INV-'+today.replace('-','')+'-001' in invoice)
    check('Fresh invoice has nullable cost and independent print module','data-testid="total-cost">-' in invoice and 'invoice-print.js?v=1' in invoice)
    status,_,_=consumer.request('customers');check('Consumer blocked from admin page',status==403)
    for group in ['working','done']:
        status,body,_=admin.request('orders',params={'status':group})
        check('Extracted group filter '+group+' works',status==200 and ('Pelanggan Paket Uji' in body)==(group=='working'))
    check('Source config unavailable through public root',consumer.request(path='.env')[0] in [403,404])
    # Fixed public_html: private application stays intact; wrapper points outside.
    host=RUN/'hosting';private=host/'faeyza';web=host/'public_html'
    shutil.copytree(app,private);shutil.copytree(app/'public',web)
    (web/'index.php').write_bytes((private/'deploy/shared-hosting/index.php.example').read_bytes())
    hosted=Client(server(private,8083,web,web/'router.php'))
    status,body,_=hosted.request('login');check('Fixed public_html wrapper serves customer login',status==200 and 'data-password-toggle' in body)
    status,_,_=hosted.request(path='admin/');check('Fixed public_html keeps dedicated admin login',status==200)
    status,body,_=hosted.request('dashboard',data=None)
    csrf=hosted.token(path='admin/');status,_,_=hosted.request(path='admin/',data={'csrf':csrf,'phone':admin_phone,'password':password})
    status,body,_=hosted.request('invoice',params={'id':order_id});check('Fixed public_html invoice reads private icons and signature assets',status==200 and 'mdi-icon' in body and 'NOTA SERVICE' in body)
    check('Fixed public_html public logo available',hosted.request(path='assets/faeyza-logo.jpg')[0]==200)
    check('Fixed public_html does not expose private config',hosted.request(path='.env')[0]==404 and hosted.request(path='config/branding.php')[0]==404)
    # XAMPP-like subdirectory layout; Apache itself is not installed here.
    htdocs=RUN/'xampp/htdocs';bengkel=htdocs/'bengkel';shutil.copytree(app,bengkel)
    router=RUN/'subfolder-router.php';router.write_text('<?php if(!str_starts_with(parse_url($_SERVER["REQUEST_URI"],PHP_URL_PATH),"/bengkel/public/")){http_response_code(404);return true;}return false;',encoding='utf-8')
    subbase=server(bengkel,8084,htdocs,router)+'bengkel/public/';sub=Client(subbase)
    status,body,_=sub.request('login');check('XAMPP-like subfolder login resolves assets base',status==200 and '<base href="/bengkel/public/">' in body)
    csrf=sub.token(path='admin/');status,_,headers=sub.request(path='admin/',data={'csrf':csrf,'phone':admin_phone,'password':password})
    check('XAMPP-like subfolder admin redirects inside subfolder',status==303 and headers.get('Location','').startswith('/bengkel/public/index.php'))
    status,body,_=sub.request('invoice',params={'id':order_id});check('XAMPP-like subfolder invoice opens',status==200 and 'INV-' in body)
    check('XAMPP-like subfolder static assets served',sub.request(path='assets/app.js?v=10')[0]==200)
    # Keep a fingerprint of actual runtime content verified before the report is added.
    (RUN/'verified-manifest.json').write_text(json.dumps(manifest),encoding='utf-8')
except Exception as error:
    if not results or results[-1]['status']!='FAIL':
        results.append({'name':'Package verification runtime','status':'FAIL','error':str(error)})
    raise
finally:
    for process,log in servers:
        process.terminate();process.wait(timeout=10);log.close()
    if created:
        php(app,'-r',guard+'Database::connection(false)->query("DROP DATABASE `".$n."`");')
        print('Disposable delivery database removed; other databases unchanged.',flush=True)
    for config_file in RUN.rglob('.env'):config_file.unlink()
    summary={'pass':sum(r['status']=='PASS' for r in results),'fail':sum(r['status']=='FAIL' for r in results),'blocked':1,'blocked_reason':'Actual XAMPP Apache and hosting control panel are not installed/available; PHP/MariaDB and equivalent directory layouts tested.','results':results,'run_directory':str(RUN)}
    (ROOT/'tests/artifacts/delivery-package-results.json').write_text(json.dumps(summary,indent=2),encoding='utf-8')
    print(json.dumps({key:summary[key] for key in ['pass','fail','blocked']}),flush=True)
