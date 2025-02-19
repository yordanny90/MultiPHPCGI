@echo off
setlocal
call "%~dp0bin\utils.bat"
set "phpdir=%~dp0app\phpdir.txt"
set /p php_ver=<"%phpdir%"
if "%php_ver%" == "" (
	echo Debe indicar la carpeta de PHP en "%phpdir%"
	exit /b 1
)
set "php_exe=%~dp0bin\php\%php_ver%\php.exe"
set "php_ini=%~dp0bin\php\%php_ver%\php.ini"
if not exist "%php_ini%" (
	call "%~dp0bin\install-php.bat" %php_ver%
	if %ERRORLEVEL% neq 0 (
		exit /b %ERRORLEVEL%
	)
)
if "%1"=="" (
	%hidec% "%php_exe%" -f "%~dp0app\app.php"
) else (
	"%php_exe%" -f "%~dp0app\app.php" -- %*
)
