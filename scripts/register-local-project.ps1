param(
    [Parameter(Mandatory = $true)][string]$Key,
    [Parameter(Mandatory = $true)][string]$ProjectEnv,
    [Parameter(Mandatory = $true)][string]$Url
)

$ErrorActionPreference = 'Stop'
$root = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$centralEnv = Join-Path $root '.env'
$source = (Resolve-Path -LiteralPath $ProjectEnv).Path
$secretLine = Get-Content -LiteralPath $source | Where-Object { $_ -match '^RQCODE_INTEGRATION_SECRET=' } | Select-Object -First 1
if (-not $secretLine) { throw 'Segredo da integração ausente.' }
$secret = $secretLine.Substring($secretLine.IndexOf('=') + 1).Trim('"')
if ($secret.Length -lt 32) { throw 'Segredo inválido.' }

$prefix = ($Key.ToUpper() -replace '[^A-Z0-9]+', '_')
$content = [IO.File]::ReadAllText($centralEnv)
$values = [ordered]@{
    "${prefix}_INTEGRATION_URL" = $Url
    "${prefix}_INTEGRATION_SECRET" = $secret
}
foreach ($item in $values.GetEnumerator()) {
    $escaped = [Regex]::Escape($item.Key)
    if ($content -match "(?m)^$escaped=") {
        $content = [Regex]::Replace($content, "(?m)^$escaped=.*$", "$($item.Key)=$($item.Value)")
    } else {
        $content += "`r`n$($item.Key)=$($item.Value)"
    }
}
[IO.File]::WriteAllText($centralEnv, $content, [Text.UTF8Encoding]::new($false))
Write-Host "[OK] $Key registrado na Central RQCODE sem expor o segredo."
