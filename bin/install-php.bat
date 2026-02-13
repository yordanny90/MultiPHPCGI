@echo off
setlocal
call "%~dp0_load.bat"
set "php_ver=%~1"
set ux=0
if "%php_ver%"=="-ux" (
    set ux=1
    set "php_ver="
	set /P "php_ver=Ingrese una version de PHP: "
)
if "%php_ver%"=="" (
	echo Debe indicar una version de PHP para instalar.
	if %ux%==1 ( pause )
	exit /b 1
)
set "php_dir=%~dp0..\php\%php_ver%"
set "php_exe=%php_dir%\php.exe"
set "_tmp=%~dp0..\tmp"
if not exist "%_tmp%" (
	@mkdir "%_tmp%"
)

echo Instalacion de PHP version %php_ver%
if not exist "%php_exe%" ( goto install )
if "%~2"=="rebuild" ( goto rebuild )
if not exist "%php_dir%\php.ini" ( goto rebuild )
echo La instalacion ya existe.
if %ux%==1 ( pause )
exit /b 0

:install
echo Buscando PHP %php_ver%...
set php_url=
for /F "usebackq delims=" %%a IN (`call "%~dp0download_php_nts_list.bat" -v ^| findstr "php-%php_ver%-"`) do (
    SET "php_url=%%a"
    goto done
)
:done
for /f %%a in ('call curl -I -s -w "%%{http_code}" "%%php_url%%"') do (
	if "%%a"=="200" (
	    goto found
	)
)
echo Error: Version de PHP no encontrada. >&2
if %ux%==1 ( pause )
exit /b 1

:found
echo Encontrado!
echo Descargando: %php_url%
set "zipfile=php-%php_ver%-nts.zip"
call curl -s -o "%_tmp%\%zipfile%" "%php_url%"
if %ERRORLEVEL% neq 0 (
	echo Error: No se pudo descargar el archivo.
	if %ux%==1 ( pause )
	exit /b %ERRORLEVEL%
)

if not exist "%_tmp%\%zipfile%" (
	echo Error: No se pudo guardar el archivo.
	if %ux%==1 ( pause )
	exit /b 1
)

echo "%_tmp%\%zipfile%"

echo Descomprimiendo ZIP...
call 7za x -y "%_tmp%\%zipfile%" "-o%php_dir%"
if %ERRORLEVEL% neq 0 (
	del "%_tmp%\%zipfile%"
	echo Error: No se pudo descomprimir el archivo.
	if %ux%==1 ( pause )
	exit /b %ERRORLEVEL%
)

echo Eliminando archivo ZIP...
del "%_tmp%\%zipfile%"

if not exist "%php_dir%\php.exe" (
	echo Error: No se pudo completar la instalacion.
	if %ux%==1 ( pause )
	exit /b 1
)

:rebuild
call "%~dp0rebuild-php.bat" %php_ver%
if %ERRORLEVEL% neq 0 (
    if %ux%==1 ( pause )
    exit /b %ERRORLEVEL%
)
echo PHP %php_ver% instalado correctamente!
if %ux%==1 ( pause )
echo Generando archivos:
call "%~dp0mphpcgi.bat" php-bat
echo.
if %ux%==1 ( pause )

exit /b 0