@echo off
setlocal
set "php_ini=%~1"
if not exist "%php_ini%" ( exit /b 1 )
set "ext=%~2"

if "%ext%"=="" (
    echo Debe indicar el nombre de una extension>&2
    exit /b 1
)
for /f "tokens=1" %%a in ('call extphp-listnul.bat "%php_ini%"') do (
    if "%ext%"=="%%~a" (
        goto found
    )
)
echo Inexistente "%ext%">&2
exit /b 1
:found
exit /b 0