@echo off
setlocal
call "%~dp0_load.bat"
set "php_ver=%1"
set "php_dir=%~dp0..\php\%php_ver%"
set "php_exe=%php_dir%\php.exe"
set "_tmp=%~dp0..\tmp"
if "%php_ver%"=="" (
	echo Debe indicar una version de PHP para instalar.
	exit /b 1
)

echo Instalacion de PHP version %php_ver%
if not exist "%php_exe%" ( goto install )
if "%2"=="rebuild" ( goto rebuild )
if not exist "%php_dir%\php.ini" ( goto rebuild )
echo La instalacion ya existe.
pause
exit /b %ERRORLEVEL%

:install
set "tmp_php_list=%~dp0..\tmp\php_nts-%php_ver%.txt"
echo Buscando PHP %php_ver%...
FOR /F "usebackq delims=" %%a IN (`call "%~dp0download_php_nts_list.bat" 0`) DO (
    SET "f=%%a"
    GOTO :done
)
:done
type %f% | findstr "php-%php_ver%-" > "%tmp_php_list%"
if %ERRORLEVEL% neq 0 (
    echo Error: PHP no encontrado
    pause
	exit /b %ERRORLEVEL%
)

set /p php_url=< "%tmp_php_list%"
for /f %%a in ('call curl -I -s -w "%%{http_code}" "%%php_url%%"') do (
	if "%%a"=="200" ( goto found )
)
echo Error: Version de PHP invalida.
pause
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
	pause
	exit /b %ERRORLEVEL%
)

if not exist "%_tmp%\%zipfile%" (
	echo Error: No se pudo guardar el archivo.
	pause
	exit /b 1
)

echo "%_tmp%\%zipfile%"

echo Descomprimiendo ZIP...
call 7za x -y "%_tmp%\%zipfile%" "-o%php_dir%"
if %ERRORLEVEL% neq 0 (
	del "%_tmp%\%zipfile%"
	echo Error: No se pudo descomprimir el archivo.
	pause
	exit /b %ERRORLEVEL%
)

echo Eliminando archivo ZIP...
del "%_tmp%\%zipfile%"

if not exist "%php_dir%\php.exe" (
	echo Error: No se pudo completar la instalacion.
	pause
	exit /b 1
)

:rebuild
call "%~dp0rebuild-php.bat" %php_ver%
if %ERRORLEVEL% neq 0 (
    pause
    exit /b %ERRORLEVEL%
)
:end
pause
exit /b 0