@echo off
setlocal
powershell -File "%~1" >"%~2.tmp"
if %ERRORLEVEL% == 0 (
    copy /Y "%~2.tmp" "%~2" >nul 2>&1
)
del "%~2.tmp" >nul 2>&1