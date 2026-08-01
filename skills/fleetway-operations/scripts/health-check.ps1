[CmdletBinding()]
param(
    [string]$HostName = 'root@fleetway.com.br',
    [string]$RemotePath = '/var/www/fleetway.com.br'
)
$ErrorActionPreference = 'Stop'
if ($RemotePath -notmatch '^/var/www/[A-Za-z0-9._-]+/?$') { throw "Destino recusado: $RemotePath" }
Write-Host '[Fleetway] DNS e HTTPS'
Resolve-DnsName fleetway.com.br -ErrorAction Stop | Where-Object Type -eq 'A' | Select-Object Name,IPAddress
$status = & curl.exe -sS -o NUL -w '%{http_code}' 'https://fleetway.com.br/'
if ($LASTEXITCODE -ne 0 -or [int]$status -ge 500) { throw "HTTPS indisponivel: $status" }
Write-Host "HTTPS_STATUS=$status"
Write-Host '[Fleetway] SSH, destino e serviços'
ssh -o BatchMode=yes -o ConnectTimeout=10 $HostName "set -e; test -d '$RemotePath'; test -f '$RemotePath/.env'; systemctl is-active nginx; systemctl is-active php8.3-fpm; df -P '$RemotePath' | tail -n 1"
if ($LASTEXITCODE -ne 0) { throw 'Health check remoto falhou.' }
Write-Host '[OK] Health check concluido.'
