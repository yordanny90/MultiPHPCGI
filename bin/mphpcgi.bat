@echo off
setlocal
set "phpdir=%~dp0..\inc\phpdir.txt"
set /p php_ver=<"%phpdir%"
if "%php_ver%" == "" (
	echo Debe indicar la carpeta de PHP en "%phpdir%"
	exit /b 1
)
set phpdir=
if not exist "%~dp0..\php\%php_ver%\php.ini" (
	call "%~dp0install-php.bat" %php_ver%
	if %ERRORLEVEL% neq 0 (
		exit /b %ERRORLEVEL%
	)
)
if not exist "%~dp0..\conf\ssl\localhost.crt" (
	call "%~dp0cert_generate.bat"
	if %ERRORLEVEL% neq 0 (
		exit /b %ERRORLEVEL%
	)
)
call "%~dp0mphpcgi-load.bat"
set PATH=%~dp0..\php\%php_ver%;%PATH%
set php_ver=
if "%1"=="app-start" (
	echo En segundo plano...
	call hidec php -f "%~dp0..\app\app.php" -- %*
	exit /b
)
"%~dp0mphp.bat" -f "%~dp0..\app\app.php" -- %*
