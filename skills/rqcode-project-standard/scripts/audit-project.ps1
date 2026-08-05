param(
    [Parameter(Mandatory = $true)]
    [string]$ProjectPath
)

$resolved = (Resolve-Path -LiteralPath $ProjectPath -ErrorAction Stop).Path
$searchFiles = @()
Push-Location -LiteralPath $resolved
try {
    $searchFiles = & rg --files -g '*.php' -g '*.js' -g '*.ts' -g '*.tsx' -g '*.md' -g '!vendor/**' -g '!node_modules/**' -g '!.git/**' -g '!storage/**' -g '!dist/**' -g '!build/**' |
        ForEach-Object { Get-Item -LiteralPath (Join-Path $resolved $_) }
} finally {
    Pop-Location
}
$hasRqcode = [bool]($searchFiles | Select-String -Pattern 'RQCODE|rqcode' | Select-Object -First 1)
$hasVoice = [bool]($searchFiles | Where-Object { $_.Extension -in '.php','.js','.ts','.tsx' } | Select-String -Pattern 'SpeechRecognition|webkitSpeechRecognition|voice' | Select-Object -First 1)
$hasLocaleCatalog = [bool]($searchFiles | Where-Object { $_.Extension -in '.php','.js','.ts','.tsx' } | Select-String -Pattern 'pt-BR|en-US|es-ES' | Select-Object -First 1)
$hasOffline = (Test-Path -LiteralPath (Join-Path $resolved 'public\sw.js')) -and [bool]($searchFiles | Where-Object { $_.Extension -in '.php','.js','.ts','.tsx' } | Select-String -Pattern 'indexedDB|OfflineRequest|offline-first' | Select-Object -First 1)

$checks = [ordered]@{
    project_path       = $resolved
    php                = (Test-Path -LiteralPath (Join-Path $resolved 'composer.json'))
    env_example        = (Test-Path -LiteralPath (Join-Path $resolved '.env.example'))
    migrations         = (Test-Path -LiteralPath (Join-Path $resolved 'database\migrations')) -or (Test-Path -LiteralPath (Join-Path $resolved 'bin\migrate.php'))
    seeds              = (Test-Path -LiteralPath (Join-Path $resolved 'database\seeds')) -or (Test-Path -LiteralPath (Join-Path $resolved 'database\seeders')) -or (Test-Path -LiteralPath (Join-Path $resolved 'bin\migrate.php'))
    local_bat          = ((Get-ChildItem -LiteralPath $resolved -Filter '*.bat' -File -ErrorAction SilentlyContinue).Count -gt 0)
    deploy_script      = (Test-Path -LiteralPath (Join-Path $resolved 'deploy-vps.bat')) -or (Test-Path -LiteralPath (Join-Path $resolved 'scripts\deploy-vps.ps1'))
    translations       = (Test-Path -LiteralPath (Join-Path $resolved 'resources\lang')) -or (Test-Path -LiteralPath (Join-Path $resolved 'app\Lang')) -or [bool]($searchFiles | Where-Object { $_.Name -match 'i18n|translations|messages' } | Select-Object -First 1) -or $hasLocaleCatalog
    rqcode_integration = $hasRqcode
    voice              = $hasVoice
    offline_first      = $hasOffline
}

[pscustomobject]$checks | ConvertTo-Json
