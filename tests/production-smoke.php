<?php
declare(strict_types=1);
// Non-destructive HTTP checks only: no database login, registration, seed or reset.
$base=rtrim($argv[1]??'', '/');
if (!filter_var($base,FILTER_VALIDATE_URL) || parse_url($base,PHP_URL_SCHEME)!=='https' || parse_url($base,PHP_URL_PATH)) {
    fwrite(STDERR,"Usage: php tests/production-smoke.php https://hostname\n"); exit(1);
}
$results=[]; $cookie=tempnam(sys_get_temp_dir(),'faeyza_smoke_');
function verify(bool $ok,string $name): void {
    global $results; $results[]=['name'=>$name,'status'=>$ok?'PASS':'FAIL'];
    echo ($ok?'PASS ':'FAIL ').$name.PHP_EOL;
}
function http(string $path,string $method='GET',array $data=[]): array {
    global $base,$cookie;
    $h=curl_init($base.$path);
    curl_setopt_array($h,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_HEADER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_TIMEOUT=>20,CURLOPT_COOKIEFILE=>$cookie,CURLOPT_COOKIEJAR=>$cookie]);
    if (PHP_OS_FAMILY==='Windows' && defined('CURLSSLOPT_NATIVE_CA')) curl_setopt($h,CURLOPT_SSL_OPTIONS,CURLSSLOPT_NATIVE_CA);
    if($method==='POST')curl_setopt_array($h,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>http_build_query($data)]);
    $raw=curl_exec($h);
    if($raw===false)throw new RuntimeException('TLS/HTTP request failed: '.curl_error($h));
    $size=(int)curl_getinfo($h,CURLINFO_HEADER_SIZE);
    return ['status'=>(int)curl_getinfo($h,CURLINFO_HTTP_CODE),'headers'=>substr($raw,0,$size),'body'=>substr($raw,$size)];
}
try {
    $root=http('/');
    verify($root['status']===303 && str_contains($root['headers'],'page=login'),'Guest redirected to login');
    $login=http('/index.php?page=login'); $body=$login['body']; $headers=$login['headers'];
    verify($login['status']===200,'Login returns HTTP 200 over verified TLS');
    verify(str_contains($body,'Faeyza Elektronik Service'),'Correct application branding');
    verify(str_contains($body,'assets/faeyza-logo.jpg'),'Official logo is rendered');
    verify(str_contains($body,'data-mdi='),'Local MDI icons are rendered');
    verify((bool)preg_match('/Set-Cookie:.*; secure.*; HttpOnly.*SameSite=Lax/i',$root['headers'].$headers),'Secure HttpOnly SameSite session cookie');
    verify(str_contains(strtolower($headers),'content-security-policy:'),'CSP header');
    verify(str_contains(strtolower($headers),'x-frame-options: deny'),'Frame protection');
    verify(str_contains(strtolower($headers),'x-content-type-options: nosniff'),'Content type protection');
    verify(str_contains(strtolower($headers),'cache-control: no-store'),'Private pages are not cached');
    preg_match('/name="csrf" value="([a-f0-9]{64})"/',$body,$match);
    verify(isset($match[1]),'CSRF token exists');
    verify(http('/index.php?page=login','POST',['phone'=>'invalid','password'=>'invalid'])['status']===419,'Login POST without CSRF rejected');
    verify(http('/index.php?page=register','POST',['phone'=>'invalid'])['status']===419,'Registration POST without CSRF rejected');
    verify(http('/index.php?page=customers')['status']===303,'Guest cannot access admin endpoint');
    verify(http('/index.php?page=invoice&id=1')['status']===303,'Guest cannot read invoice');
    verify(http('/index.php?page%5B%5D=invalid')['status']===404,'Malformed route rejected safely');
    $bad=http('/index.php?page=login','POST',['csrf'=>$match[1]??'','phone'=>"' OR 1=1 --",'password'=>'InvalidSmokePassword']);
    verify($bad['status']===422 && !str_contains(strtolower($bad['body']),'mysqli_sql_exception'),'Invalid login handled without SQL disclosure');
    foreach(['/.env','/app/bootstrap.php','/config/branding.php','/config/assets/faeyza-ttd.png','/database/schema.sql','/bin/setup.php','/storage/logs/php.log','/tests/run.php','/.git/config']as $path) {
        $r=http($path); verify(in_array($r['status'],[403,404],true) && !str_contains($r['body'],'<?php'),'Private source blocked: '.$path);
    }
    verify(http('/assets/app.css')['status']===200,'Application stylesheet available');
    verify(http('/assets/app.js')['status']===200,'Application JavaScript available');
    verify(http('/assets/faeyza-logo.jpg')['status']===200,'Logo asset available');
    verify(http('/assets/print.css')['status']===200,'Print stylesheet available');
    verify(http('/assets/invoice.css')['status']===200,'Invoice stylesheet available');
    verify(http('/index.php?page=account')['status']===303,'Account settings require login');
    verify(http('/index.php?page=customer-password&id=1')['status']===303,'Customer password reset requires login');
    $register=http('/index.php?page=register');
    verify($register['status']===200 && substr_count($register['body'],'minlength="6"')===2,'Registration uses six-character minimum');
} catch(Throwable $e) { verify(false,$e->getMessage()); }
finally { if(is_file($cookie))unlink($cookie); }
$failed=count(array_filter($results,fn($r)=>$r['status']==='FAIL'));
$report=['base'=>$base,'checked_at'=>gmdate('c'),'pass'=>count($results)-$failed,'fail'=>$failed,'blocked'=>0,'results'=>$results];
file_put_contents(__DIR__.'/artifacts/production-smoke-results.json',json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
echo json_encode(['pass'=>$report['pass'],'fail'=>$failed,'blocked'=>0]).PHP_EOL;
exit($failed?1:0);
