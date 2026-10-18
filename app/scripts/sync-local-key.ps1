$ErrorActionPreference = 'Stop'
$root = Split-Path $PSScriptRoot -Parent
$source = [IO.File]::ReadAllText((Join-Path $root '.env'))
$targetPath = Join-Path $root '.env.local'
$target = [IO.File]::ReadAllText($targetPath)
$pattern = '(?m)^APP_KEY=[^\r\n]*'
$keys = [regex]::Matches($source, $pattern)
if ($keys.Count -ne 1) { throw '.env harus mempunyai tepat satu baris APP_KEY.' }
$value = $keys[0].Value.Substring(8).Trim().Trim([char]34).Trim([char]39)
if ($value.StartsWith('base64:')) {
    try { $bytes = [Convert]::FromBase64String($value.Substring(7)) } catch { throw 'Base64 key sumber tidak valid; tidak ada file diubah.' }
} else {
    $bytes = [Text.Encoding]::UTF8.GetBytes($value)
}
if ($bytes.Length -ne 32) { throw 'Key sumber bukan 32 bytes; tidak ada file diubah.' }
if ([regex]::Matches($target, $pattern).Count -ne 1) { throw '.env.local harus mempunyai tepat satu baris APP_KEY.' }
$replacement = 'APP_KEY=' + $value
$updated = [regex]::Replace($target, $pattern, [System.Text.RegularExpressions.MatchEvaluator]{ param($match) $replacement })
[IO.File]::WriteAllText($targetPath, $updated, [Text.UTF8Encoding]::new($false))
Write-Host 'APP_KEY valid disalin dari .env ke .env.local. Nilai key tidak ditampilkan.'
