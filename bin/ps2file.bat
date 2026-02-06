@echo off
setlocal
set dl=1
if "%3"=="0" set dl=0
if not exist "%2" set dl=1
if %dl%==1 (
    powershell -File "%1" >"%2.tmp"
    if %ERRORLEVEL% == 0 (
        copy /Y "%2.tmp" "%2" >nul 2>&1
    )
    del "%2.tmp"
)
@type "%2"