@echo off
setlocal
call "%~dp0utils.bat"
set "php_ver=%1"
set "php_dir=%~dp0php\%php_ver%"
set "php_exe=%php_dir%\php.exe"
set "tmp=%~dp0..\tmp"
if "%php_ver%"=="" (
	echo Debe indicar una version de PHP para instalar.
	exit /b 1
)

echo Instalacion de PHP version %php_ver%
if exist "%php_exe%" (
	if "%2"=="rebuild" (
		call "%~dp0rebuild-php.bat" %php_ver%
		exit /b %ERRORLEVEL%
	)
	if not exist "%php_dir%\php.ini" (
		call "%~dp0rebuild-php.bat" %php_ver%
		exit /b %ERRORLEVEL%
	)
	echo La instalacion ya existe.
	exit /b %ERRORLEVEL%
)

set "url=https://windows.php.net/downloads/releases/archives/"
set "phpbase=%php_ver:~0,2%"
if "%phpbase%"=="5." (
	set "zipfile=php-%php_ver%-nts-Win32-vc11-x64.zip"
	for /f %%a in ('%%curl%% -I -s -w "%%{http_code}" "%%url%%%%zipfile%%"') do if "%%a"=="200" (goto found)
	set "zipfile="
) else if "%phpbase%"=="7." (
	set "zipfile=php-%php_ver%-nts-Win32-vc14-x64.zip"
	for /f %%a in ('%%curl%% -I -s -w "%%{http_code}" "%%url%%%%zipfile%%"') do if "%%a"=="200" (goto found)
	set "zipfile=php-%php_ver%-nts-Win32-vc15-x64.zip"
	for /f %%a in ('%%curl%% -I -s -w "%%{http_code}" "%%url%%%%zipfile%%"') do if "%%a"=="200" (goto found)
	set "zipfile="
) else if "%phpbase%"=="8." (
	set "zipfile=php-%php_ver%-nts-Win32-vs16-x64.zip"
	for /f %%a in ('%%curl%% -I -s -w "%%{http_code}" "%%url%%%%zipfile%%"') do if "%%a"=="200" (goto found)
	set "zipfile=php-%php_ver%-nts-Win32-vs17-x64.zip"
	for /f %%a in ('%%curl%% -I -s -w "%%{http_code}" "%%url%%%%zipfile%%"') do if "%%a"=="200" (goto found)
	set "zipfile=php-%php_ver%-nts-Win32-vs18-x64.zip"
	for /f %%a in ('%%curl%% -I -s -w "%%{http_code}" "%%url%%%%zipfile%%"') do if "%%a"=="200" (goto found)
	set "zipfile="
) else (
	echo Error: Version de PHP invalida.
	exit /b 1
)
:found
if "%zipfile%"=="" (
	echo Error: Version de PHP no encontrada.
	exit /b 2
)
@mkdir "%tmp%"
%curl% -s -o "%tmp%\%zipfile%" "%url%%zipfile%"
if %ERRORLEVEL% neq 0 (
	exit /b %ERRORLEVEL%
)

if not exist "%tmp%\%zipfile%" (
	echo Error: No se pudo descargar el archivo.
	exit /b 1
)

echo "%tmp%\%zipfile%"

echo Descomprimiendo ZIP...
%zip7% x -y "%tmp%\%zipfile%" "-o%php_dir%"
if %ERRORLEVEL% neq 0 (
	del "%tmp%\%zipfile%"
	echo Error: No se pudo descomprimir el archivo.
	exit /b %ERRORLEVEL%
)

echo Eliminando archivo ZIP...
del "%tmp%\%zipfile%"

if not exist "%php_dir%\php.exe" (
	echo Error: No se pudo completar la instalacion.
	exit /b 1
)

call "%~dp0rebuild-php.bat" %php_ver%