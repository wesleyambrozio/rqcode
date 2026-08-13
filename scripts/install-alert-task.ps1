param([string]$TaskName='RQCODE-Avisos-Diarios',[string]$Time='08:00')
$ErrorActionPreference='Stop'
$project=(Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '..')).Path
$batch=Join-Path $project 'run-alerts.bat'
if(-not(Test-Path -LiteralPath $batch)){throw 'run-alerts.bat não encontrado.'}
$command=(Get-Command cmd.exe -ErrorAction Stop).Source
$action=New-ScheduledTaskAction -Execute $command -Argument ('/d /c ""{0}""' -f $batch) -WorkingDirectory $project
$trigger=New-ScheduledTaskTrigger -Daily -At $Time
$settings=New-ScheduledTaskSettingsSet -StartWhenAvailable -ExecutionTimeLimit (New-TimeSpan -Minutes 10) -MultipleInstances IgnoreNew
Register-ScheduledTask -TaskName $TaskName -Action $action -Trigger $trigger -Settings $settings -Description 'Envia alertas RQCODE de contas de clientes e vencimentos financeiros.' -Force | Out-Null
Write-Output "Tarefa '$TaskName' configurada diariamente às $Time."
