@echo off
setlocal
if "%1"=="service-start" (
    if not exist "%~dp0..\conf\ssl\localhost.crt" (
        start /WAIT cmd /c call "%~dp0cert_generate.bat"
    )
)
call "%~dp0mphp.bat" -f "%~dp0app\cli.php" -- %*
