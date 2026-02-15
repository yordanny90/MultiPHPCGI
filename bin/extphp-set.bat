@echo off
setlocal
set "php_ver=%~1"
set "php_ini=%~2"
set "ext=%~3"
if "%php_ver%"=="" (
    echo Debe indicar una version de PHP para configurar>&2
    echo "version" "php.ini" "extension">&2
    exit /b 1
)
if not exist "%php_ini%" (
    echo El archivo "%php_ini%" no existe>&2
    exit /b 1
)
if "%ext%"=="" (
    echo Debe indicar el nombre de una extension>&2
    exit /b 1
)
set "php_dir=%~dp0..\php\%php_ver%"
set "php_exe=%php_dir%\php.exe"
if not exist "%php_exe%" (
    echo La instalación de php %php_ver% no existe>&2
    exit /b 1
)

:make
powershell -Command "(Get-Content '%php_ini%') -replace '^;((zend_)?extension=(php_)?(%ext%)(.dll)?)\b', '$1' | Set-Content '%php_ini%'"
if %ERRORLEVEL% neq 0 (
    echo Error: No se pudo activar la extension %ext%>&2
    goto error
)
call "%~dp0test-phpini.bat" "%php_ver%" "%php_ini%" "%ext%"
if %ERRORLEVEL% neq 0 (
    call "%~dp0extphp-unset.bat" "%php_ver%" "%php_ini%" "%ext%"
    if %ERRORLEVEL% neq 0 (
        goto error
    )
)
goto done

:done
echo Extension %ext% habilitada
exit /b 0

:error
if %ERRORLEVEL% neq 0 ( exit /b %ERRORLEVEL% )
exit /b 1
