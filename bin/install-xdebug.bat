@echo off
setlocal
call "%~dp0_load.bat"
set "php_ver=%~1"
set "php_dir=%~dp0..\php\%php_ver%"
set "php_exe=%php_dir%\php.exe"
if "%php_ver%"=="" (
	echo Debe indicar una version de PHP para configurar.
	exit /b 1
)

echo Instalacion de XDEBUG
if not exist "%php_dir%\php.ini" (
	echo La instalacion no esta configurada.
	exit /b 1
)

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
echo Error: Version de XDEBUG PHP no encontrada. >&2
if %ux%==1 ( pause )
exit /b 1

:found
echo Encontrado!
echo Descargando %xdebug_url%
call curl -s -o "%tmp_xdebug%" "%xdebug_url%"
if %ERRORLEVEL% neq 0 (
	exit /b %ERRORLEVEL%
)
@echo off
copy /Y "%tmp_xdebug%" "%php_dir%\ext\php_xdebug.dll"
if %ERRORLEVEL% neq 0 (
	exit /b %ERRORLEVEL%
)
del "%tmp_xdebug%"

set "php_ini=%php_dir%\php.ini.tmp"
if exist "%php_ini%" del "%php_ini%"
findstr /x /i "zend_extension=xdebug" "%php_dir%\php.ini" >nul
if errorlevel 1 (
	echo Copiando php.ini...
	copy /Y "%php_dir%\php.ini" "%php_ini%"
	echo Modificando php.ini...
	echo.>>"%php_ini%"
	echo.[xdebug]>>"%php_ini%"
	echo.zend_extension=xdebug>>"%php_ini%"
	echo.xdebug.mode=debug>>"%php_ini%"
	echo.xdebug.client_host=localhost>>"%php_ini%"
	echo.xdebug.client_port=9000>>"%php_ini%"
	echo.; Configuracion XDEBUG generada por MultiPHPCGI>>"%php_ini%"
)
for /f "tokens=*" %%i in ('powershell -Command "& { & '%php_exe%' -c '%php_ini%' -m 2>&1 | Select-String 'warning|error' }"') do (
	echo Error: Se detecto un warning o error al comprobar PHP.
	echo %%i
	exit /b 1
)
if %ERRORLEVEL% neq 0 (
	echo Error: Se detecto un warning o error en PHP.
	exit /b %ERRORLEVEL%
)
if exist "%php_ini%" (
	copy /Y "%php_ini%" "%php_dir%\php.ini"
	if %ERRORLEVEL% neq 0 (
		echo Error: No se pudo guardar el php.ini.
		exit /b %ERRORLEVEL%
	)
	del "%php_ini%"
)
echo Completo!
