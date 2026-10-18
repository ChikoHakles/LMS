$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot
$phpCommand = Get-Command php -ErrorAction SilentlyContinue
if ($phpCommand) { $php = $phpCommand.Source }
elseif (Test-Path 'C:\xampp\php\php.exe') { $php = 'C:\xampp\php\php.exe' }
else { throw 'PHP CLI tidak ditemukan. Aktifkan PHP di PATH atau periksa C:\xampp\php\php.exe.' }
$npmCommand = Get-Command npm.cmd -ErrorAction SilentlyContinue
if (-not $npmCommand) { throw 'npm.cmd tidak ditemukan. Node.js harus tersedia di PATH.' }
if (-not (Test-Path 'vendor\autoload.php')) { throw 'vendor belum tersedia. Jalankan composer install terlebih dahulu.' }
if (-not (Test-Path 'node_modules\vite\bin\vite.js')) { throw 'Vite belum tersedia. Jalankan npm install dari terminal Windows terlebih dahulu.' }
$root = $PSScriptRoot.Replace("'", "''")
$phpPath = $php.Replace("'", "''")
$npmPath = $npmCommand.Source.Replace("'", "''")
foreach ($port in @(8000, 5173)) {
    if (Get-NetTCPConnection -LocalPort $port -State Listen -ErrorAction SilentlyContinue) {
        throw "Port $port sudah dipakai. Periksa proses existing; script tidak menghentikannya."
    }
}
Start-Process powershell.exe -WorkingDirectory $PSScriptRoot -ArgumentList @('-NoExit', '-Command', "Set-Location '$root'; & '$phpPath' artisan serve --host=127.0.0.1 --port=8000")
Start-Process powershell.exe -WorkingDirectory $PSScriptRoot -ArgumentList @('-NoExit', '-Command', "Set-Location '$root'; & '$npmPath' run dev -- --host=127.0.0.1 --port=5173 --strictPort")
Write-Host 'Dua terminal dibuka. Tunggu log Laravel dan Vite siap, lalu buka http://127.0.0.1:8000.'
Write-Host 'MySQL/MariaDB XAMPP harus aktif. Stop server dengan Ctrl+C di masing-masing terminal.'
