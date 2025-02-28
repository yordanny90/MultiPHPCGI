@echo off
setlocal
if "%1"=="" (
 	call "%~fs0" app-start
 	exit /b
)
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
set /p nginx_ver=<"%~dp0..\inc\nginxdir.txt"
if not exist "%~dp0..\nginx\%nginx_ver%\nginx.exe" (
	call "%~dp0install-nginx.bat" %nginx_ver%
	if %ERRORLEVEL% neq 0 (
		exit /b %ERRORLEVEL%
	)
)
set nginx_ver=
if not exist "%~dp0..\conf\ssl\localhost.crt" (
	call "%~dp0cert_generate.bat"
	if %ERRORLEVEL% neq 0 (
		exit /b %ERRORLEVEL%
	)
)
call "%~dp0utils.bat"
set PATH=%~dp0..\php\%php_ver%;%PATH%
set php_ver=
if "%1"=="app-start" (
	echo En segundo plano...
	hidec.exe php.exe -f "%~dp0..\app\app.php" -- %*
	exit /b
)
php.exe -f "%~dp0..\app\app.php" -- %*
