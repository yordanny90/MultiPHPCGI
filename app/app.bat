@echo off
setlocal
if "%1"=="" (
 	call "%~fs0" app-start
 	exit /b
)
set "phpdir=%~dp0phpdir.txt"
set /p php_ver=<"%phpdir%"
if "%php_ver%" == "" (
	echo Debe indicar la carpeta de PHP en "%phpdir%"
	exit /b 1
)
set phpdir=
if not exist "%~dp0..\bin\php\%php_ver%\php.ini" (
	call "%~dp0install-php.bat" %php_ver%
	if %ERRORLEVEL% neq 0 (
		exit /b %ERRORLEVEL%
	)
)
set /p nginx_ver=<"%~dp0nginxdir.txt"
set "nginx_exe=%~dp0bin\nginx\%nginx_ver%\nginx.exe"
if not exist "%nginx_exe%" (
	call "%~dp0install-nginx.bat" %nginx_ver%
	if %ERRORLEVEL% neq 0 (
		exit /b %ERRORLEVEL%
	)
)
set nginx_ver=
set nginx_exe=
if not exist "%~dp0..\conf\ssl\localhost.crt" (
	call "%~dp0..\cert_generate.bat"
	if %ERRORLEVEL% neq 0 (
		exit /b %ERRORLEVEL%
	)
)
call "%~dp0..\bin\utils.bat"
set PATH=%~dp0..\bin\php\%php_ver%;%PATH%
set php_ver=
if "%1"=="app-start" (
	echo En segundo plano...
	hidec.exe php.exe -f "%~dp0app.php" -- %*
	exit /b
)
php.exe -f "%~dp0app.php" -- %*
