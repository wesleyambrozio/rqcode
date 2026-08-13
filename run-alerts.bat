@echo off
setlocal
cd /d "%~dp0"
php bin\due-alerts.php >> storage\alerts.log 2>&1
exit /b %errorlevel%
