@echo off
setlocal
if "%~1"=="start" (
    if not exist "%~dp0..\usr\ssl\localhost.crt" (
        start /WAIT cmd /c call "%~dp0cert_generate.bat"
    )
)
"%~dp0mphp.bat" -f "%~dp0app\cli.php" -- %*
