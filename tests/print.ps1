param(
 [string]$Php='php',
 [string]$Chrome='C:/Program Files/Google/Chrome/Application/chrome.exe',
 [string]$Python='python',
 [string]$Poppler=''
)
$ErrorActionPreference='Stop'
$projectRoot=(Resolve-Path (Join-Path $PSScriptRoot '..')).Path
Push-Location $projectRoot
try{
 & $Php tests/render-invoices.php
 if($LASTEXITCODE){throw 'Ekspor HTML invoice gagal'}
 foreach($name in @('invoice-initial','invoice-final','invoice-long','invoice-pending')){
  $pdfPath=Join-Path $PSScriptRoot "artifacts/$name.pdf"
  $htmlPath=Join-Path $PSScriptRoot "artifacts/$name.html"
  $profilePath=Join-Path ([IO.Path]::GetTempPath()) "faeyza-print-$name"
  $started=(Get-Date).ToUniversalTime().AddSeconds(-1)
  & $Chrome --headless --disable-gpu --no-pdf-header-footer "--user-data-dir=$profilePath" "--print-to-pdf=$pdfPath" ([uri]$htmlPath).AbsoluteUri
  $ready=$false
  for($i=0;$i -lt 30;$i++){
   if((Test-Path -LiteralPath $pdfPath) -and (Get-Item -LiteralPath $pdfPath).LastWriteTimeUtc -ge $started){$ready=$true;break}
   Start-Sleep -Seconds 1
  }
  if(-not $ready){throw "PDF belum dihasilkan: $name"}
  if($Poppler){& (Join-Path $Poppler 'pdftoppm.exe') -scale-to 1400 -png $pdfPath (Join-Path $PSScriptRoot "artifacts/$name-print")}
 }
 & $Python tests/check-pdf.py
 if($LASTEXITCODE){throw 'Pemeriksaan PDF gagal'}
}finally{Pop-Location}
