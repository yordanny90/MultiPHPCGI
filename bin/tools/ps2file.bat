@echo off
setlocal
powershell -NoProfile -ExecutionPolicy Bypass -File "%~1" >"%~2.tmp"
if %ERRORLEVEL% == 0 (
    copy /Y "%~2.tmp" "%~2" >nul 2>&1
)
del "%~2.tmp" >nul 2>&1