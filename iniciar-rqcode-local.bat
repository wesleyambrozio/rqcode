@echo off
setlocal

set "REPO=%~dp0"
set "URL=http://127.0.0.1:8080/login"
cd /d "%REPO%"

where php >nul 2>nul
if errorlevel 1 (
  echo [ERRO] PHP nao encontrado no PATH.
  pause
  exit /b 1
)

powershell -NoProfile -Command "if (Get-NetTCPConnection -LocalPort 8080 -State Listen -ErrorAction SilentlyContinue) { exit 0 } else { exit 1 }"
if errorlevel 1 (
  start "RQCode - Servidor local" /min cmd /k "cd /d ""%REPO%"" && php -S 127.0.0.1:8080 -t public"
  timeout /t 2 /nobreak >nul
)

start "" "%URL%"
endlocal
