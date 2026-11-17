param([string]$Php='php',[string]$BaseUrl='http://127.0.0.1:8081/')
$ErrorActionPreference='Stop'
$projectRoot=(Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$env:APP_ENV='testing';$env:TEST_BASE_URL=$BaseUrl
Push-Location $projectRoot
try {
 $phpFiles=Get-ChildItem -LiteralPath $projectRoot -Recurse -File -Filter *.php
 foreach($file in $phpFiles){& $Php -l $file.FullName;if($LASTEXITCODE){throw "Lint gagal: $($file.FullName)"}}
 & $Php bin/setup.php
 if($LASTEXITCODE){throw 'Migrasi testing gagal'}
 & $Php tests/run.php --reset-test
 if($LASTEXITCODE){throw 'Tes gagal, lihat tests/artifacts/integration-results.json'}
}finally{Pop-Location}
