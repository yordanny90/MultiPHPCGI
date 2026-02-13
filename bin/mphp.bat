@echo off
setlocal
call "%~dp0_load.bat"
set "phpdir=%~dp0..\inc\phpdir.txt"
set /p php_ver=<"%phpdir%"
set "php_ver=%php_ver%"
if "%php_ver%" == "" (
    start cmd /c "echo.Debe indicar la carpeta de PHP en: & echo %phpdir% & echo. & pause"
    exit /b 1
)
set "phpini=%~dp0..\php\%php_ver%\php.ini"
if not exist "%phpini%" (
	start /WAIT cmd /c call "%~dp0install-php.bat" %php_ver% rebuild
	if not exist "%phpini%" (
		exit /b 1
	)
)
set "phpdir="
set "phpini="
"%~dp0..\php\%php_ver%\php.exe" %*
