@echo off
setlocal
call "%~dp0_load.bat"
set "php_ver=%~1"
set f=0
if "%~2"=="/f" (
    set f=1
)
set "php_dir=%~dp0..\php\%php_ver%"
set "php_exe=%php_dir%\php.exe"
if "%php_ver%"=="" (
	echo Debe indicar una version de PHP para configurar>&2
	exit /b 1
)

echo Instalacion de XDEBUG en PHP %php_ver%
if not exist "%php_dir%\php.ini" (
	echo La instalacion no esta configurada>&2
	exit /b 1
)
set "xdebug_dll=%php_dir%\ext\php_xdebug.dll"
if not exist "%xdebug_dll%" ( goto download )
if %f% == 1 ( goto download )
goto downloaded

:download
set "verbase=%php_ver:~0,3%"
set "tmp_xdebug=%~dp0..\tmp\php_xdebug-%verbase%.dll"
set "tmp_xdebug_list=%~dp0..\tmp\php_xdebug-%verbase%.txt"

echo Buscando XDEBUG para PHP %verbase%...
set xdebug_url=
for /F "usebackq delims=" %%a IN (`call "%~dp0download_xdebug_nts_list.bat" -v ^| find "-%verbase%-"`) do (
    SET "xdebug_url=%%a"
    goto done
)
:done
for /f %%a in ('call curl -I -s -w "%%{http_code}" "%%xdebug_url%%"') do (
	if "%%a"=="200" (
	    goto found
	)
)
echo Error: Version de XDEBUG PHP no encontrada>&2
if %ux%==1 ( pause )
exit /b 1

:found
echo Encontrado!
echo Descargando %xdebug_url%
call curl -s -o "%tmp_xdebug%" "%xdebug_url%"
if %ERRORLEVEL% neq 0 (
	exit /b %ERRORLEVEL%
)
copy /Y "%tmp_xdebug%" "%xdebug_dll%"
if %ERRORLEVEL% neq 0 (
	exit /b %ERRORLEVEL%
)
del "%tmp_xdebug%"

:downloaded
echo XDEBUG encontrado
set "php_ini=%php_dir%\php.ini"
call "%~dp0extphp-isnul.bat" "%php_ini%" xdebug 2>nul
if %ERRORLEVEL% == 0 ( goto settings )
call "%~dp0extphp-isset.bat" "%php_ini%" xdebug 2>nul
if %ERRORLEVEL% == 0 ( goto settings )

echo Agregando xdebug.ini
echo.>>"%php_ini%"
type "%~dp0..\inc\xdebug.ini">>"%php_ini%"

:settings
call "%~dp0extphp-set.bat" "%php_ver%" "%php_ini%" xdebug
call "%~dp0test-phpini.bat" "%php_ver%" "%php_ini%"
if %ERRORLEVEL% neq 0 (
	exit /b %ERRORLEVEL%
)
