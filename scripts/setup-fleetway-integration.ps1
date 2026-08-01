[CmdletBinding()]
param(
    [string]$FleetwayRoot = 'C:\desenvolvimento\fleetway',
    [string]$RqcodeRoot = 'C:\desenvolvimento\rqcode'
)
$ErrorActionPreference = 'Stop'
$fleetEnv = Join-Path $FleetwayRoot '.env'
$rqEnv = Join-Path $RqcodeRoot '.env'
foreach ($path in $fleetEnv,$rqEnv) { if (-not (Test-Path -LiteralPath $path)) { throw ".env ausente: $path" } }

$bytes = New-Object byte[] 32
$rng = [Security.Cryptography.RandomNumberGenerator]::Create()
try { $rng.GetBytes($bytes) } finally { $rng.Dispose() }
$secret = [Convert]::ToBase64String($bytes)

function Set-EnvValue([string]$Path, [string]$Key, [string]$Value) {
    $content = [IO.File]::ReadAllText($Path)
    $escaped = [Regex]::Escape($Key)
    if ($content -match "(?m)^$escaped=") { $content = [Regex]::Replace($content, "(?m)^$escaped=.*$", "$Key=$Value") }
    else { $content += "`r`n$Key=$Value" }
    [IO.File]::WriteAllText($Path, $content, [Text.UTF8Encoding]::new($false))
}

Set-EnvValue $fleetEnv 'RQCODE_SHARED_SECRET' $secret
Set-EnvValue $rqEnv 'FLEETWAY_INTEGRATION_URL' 'http://127.0.0.1:8000'
Set-EnvValue $rqEnv 'FLEETWAY_INTEGRATION_SECRET' $secret
Write-Host '[OK] Segredo HMAC gerado e gravado somente nos dois arquivos .env ignorados.'
