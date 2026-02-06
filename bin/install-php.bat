@echo off
setlocal
call "%~dp0mphpcgi-load.bat"
set "php_ver=%1"
set "php_dir=%~dp0..\php\%php_ver%"
set "php_exe=%php_dir%\php.exe"
set "_tmp=%~dp0..\tmp"
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

set "tmp_php_list=%~dp0..\tmp\php_nts-%php_ver%.txt"

echo Buscando PHP %php_ver%...
call "%~dp0download_php_nts_list.bat" 0 | findstr "php-%php_ver%-" > "%tmp_php_list%"
if %ERRORLEVEL% neq 0 (
	exit /b %ERRORLEVEL%
)

set /p php_url=< "%tmp_php_list%"
for /f %%a in ('call curl -I -s -w "%%{http_code}" "%%php_url%%"') do (
	if "%%a"=="200" (goto found)
)
echo Error: Version de PHP invalida.
exit /b 1

:found
echo Encontrado!
if not exist "%_tmp%" (
	@mkdir "%_tmp%"
)
set "zipfile=php-%php_ver%-nts.zip"
echo Descargando %php_url%
call curl -s -o "%_tmp%\%zipfile%" "%php_url%"
if %ERRORLEVEL% neq 0 (
	echo Error: No se pudo descargar el archivo.
	exit /b %ERRORLEVEL%
)

if not exist "%_tmp%\%zipfile%" (
	echo Error: No se pudo guardar el archivo.
	exit /b 1
)

echo "%_tmp%\%zipfile%"

echo Descomprimiendo ZIP...
call 7za x -y "%_tmp%\%zipfile%" "-o%php_dir%"
if %ERRORLEVEL% neq 0 (
	del "%_tmp%\%zipfile%"
	echo Error: No se pudo descomprimir el archivo.
	exit /b %ERRORLEVEL%
)

echo Eliminando archivo ZIP...
del "%_tmp%\%zipfile%"

if not exist "%php_dir%\php.exe" (
	echo Error: No se pudo completar la instalacion.
	exit /b 1
)

call "%~dp0rebuild-php.bat" %php_ver%