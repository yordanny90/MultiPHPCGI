@echo off
setlocal
set "phpdir=%~dp0..\inc\phpdir.txt"
set /p php_ver=<"%phpdir%"
if "%php_ver%" == "" (
	echo Debe indicar la carpeta de PHP en "%phpdir%"
	exit /b 1
)
set "phpbin=%~dp0..\php\%php_ver%\php.exe"
if not exist "%phpbin%" (
	call "%~dp0install-php" "%php_ver%"
	if not exist "%phpbin%" exit /b 1
)
"%phpbin%" %*
