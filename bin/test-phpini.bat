@echo off
setlocal
set "php_ver=%~1"
set "php_ini=%~2"
set "txt=%~3"
if not exist "%php_ini%" (
    echo El archivo "%php_ini%" no existe>&2
    exit /b 1
)
set "php_dir=%~dp0..\php\%php_ver%"
set "php_exe=%php_dir%\php.exe"
if not exist "%php_exe%" (
    echo La instalación de php %php_ver% no existe>&2
    exit /b 1
)

set "filter="
if "%txt%" neq "" (
    set "filter=| Select-String '%txt%'"
)
for /f "tokens=*" %%i in ('powershell -Command "& { & '%php_exe%' -c '%php_ini%' -m 2>&1 | Select-String 'warning|error|Failed loading|module could not be found' %filter% }"') do (
	echo %%i>&2
	goto error
)
if %ERRORLEVEL% neq 0 (
	echo Error al comprobar PHP>&2
	goto error
)
exit /b 0

:error
if %ERRORLEVEL% neq 0 ( exit /b %ERRORLEVEL% )
exit /b 1
