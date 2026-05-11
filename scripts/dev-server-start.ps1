Param(
    [int]$Port = 8000
)

$projectRoot = Split-Path -Parent $PSScriptRoot
$php = Get-Command php -ErrorAction Stop

# Stop previous local PHP processes (simple and reliable on Windows dev setups).
Get-Process -Name php -ErrorAction SilentlyContinue | Stop-Process -Force -ErrorAction SilentlyContinue

Start-Process -FilePath $php.Path -ArgumentList "-S 127.0.0.1:$Port -t public" -WorkingDirectory $projectRoot | Out-Null

Write-Host "Serveur demarre sur http://127.0.0.1:$Port"
