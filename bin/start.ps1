param([int]$Port=0,[switch]$TestDatabase,[string]$Php='')
$ErrorActionPreference='Stop'
$projectRoot=(Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$runtimeRoot=Join-Path (Split-Path $projectRoot -Parent) 'bengkel-runtime'
if(-not $Php){
 $candidate=Get-Command php -ErrorAction SilentlyContinue
 if($candidate){$Php=$candidate.Source}
 elseif(Test-Path (Join-Path $runtimeRoot 'php/php.exe')){$Php=Join-Path $runtimeRoot 'php/php.exe'}
 elseif(Test-Path 'C:/xampp/php/php.exe'){$Php='C:/xampp/php/php.exe'}
 else{throw 'PHP tidak ditemukan. Pasang PHP 8.2+ atau XAMPP, atau gunakan -Php path/php.exe.'}
}
if($TestDatabase){$env:APP_ENV='testing';if(-not $Port){$Port=8081}}
else{Remove-Item Env:APP_ENV -ErrorAction SilentlyContinue;if(-not $Port){$Port=8080}}
# Hanya menyalakan runtime portable milik proyek jika sudah dipersiapkan.
$dbConfig=Join-Path $runtimeRoot 'data/my.ini'
$dbExe=Join-Path $runtimeRoot 'mariadb-11.4.5-winx64/bin/mariadbd.exe'
if((Test-Path $dbConfig) -and (Test-Path $dbExe)){
 $running=Get-CimInstance Win32_Process | Where-Object {$_.ExecutablePath -eq $dbExe}
 if(-not $running){Start-Process -FilePath $dbExe -ArgumentList ('--defaults-file="'+$dbConfig+'"'),'--bind-address=127.0.0.1' -WindowStyle Hidden | Out-Null}
}
Push-Location $projectRoot
try {Write-Host "Faeyza: http://127.0.0.1:$Port — Ctrl+C untuk berhenti"; & $Php -S "127.0.0.1:$Port" -t public public/router.php;exit $LASTEXITCODE}
finally{Pop-Location}
