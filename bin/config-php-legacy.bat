@echo off
setlocal
set "php_ver=%~1"
if "%php_ver%"=="" (
	echo Debe indicar una version de PHP para configurar>&2
	exit /b 1
)
set f=0
if "%~2"=="/f" set f=1
set "ssl_dir=%~dp0..\php\%php_ver%\extras\ssl"
set "_legacy=%~dp0..\inc\part_legacy.cnf"
set "cnf=%ssl_dir%\openssl.cnf"
set "cnfnew=%ssl_dir%\openssl_legacy.cnf"
set "cnftmp=%ssl_dir%\openssl_legacy.cnf.tmp"
if not exist "%cnf%" (
    echo La instalacion no existe o no tiene openssl.cnf>&2
    exit /b 1
)

if %f%==1 goto make
if exist "%cnfnew%" (
    echo La instalacion ya tiene el legacy de openssl configurado>&2
    exit /b 0
)

:make
echo Modificando openssl_legacy.cnf %php_ver%...
copy /Y "%cnf%" "%cnftmp%"
if %ERRORLEVEL% neq 0 (
    echo Error: No se pudo guardar el nuevo cnf>&2
    exit /b %ERRORLEVEL%
)
echo.>>"%cnftmp%"
type "%_legacy%" >>"%cnftmp%"
if %ERRORLEVEL% neq 0 (
    echo Error: No se pudo guardar el nuevo cnf>&2
    exit /b %ERRORLEVEL%
)
copy /Y "%cnftmp%" "%cnfnew%"
if %ERRORLEVEL% neq 0 (
    echo Error: No se pudo guardar el nuevo cnf>&2
    exit /b %ERRORLEVEL%
)
del "%cnftmp%"
exit /b 0
