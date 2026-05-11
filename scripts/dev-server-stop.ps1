Param(
    [int]$Port = 8000
)

Get-Process -Name php -ErrorAction SilentlyContinue | Stop-Process -Force -ErrorAction SilentlyContinue

Write-Host "Serveur arrete sur le port $Port"
