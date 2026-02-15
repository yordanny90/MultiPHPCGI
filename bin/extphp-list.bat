@echo off
setlocal EnableDelayedExpansion
set "php_ini=%~1"
if not exist "%php_ini%" ( exit /b 1 )
set csv=0
if "%~2"=="/csv" set csv=1

set "list="
for /f "tokens=2 delims==" %%a in ('findstr /i /b "extension=" "%php_ini%" ^&^& findstr /i /b "zend_extension=" "%php_ini%"') do (
    for /f "tokens=1 delims=;" %%e in ("%%a") do (
        set "t=%%e"
        set "t=!t: =!"
        set "v=!t:.dll=!"
        if "!v!" neq "!t!" ( set "v=!v:php_=!" )
        if !csv! == 1 (
            set list=!list!,!v!
        ) else ( echo !v! )
    )
)
if "!list!" neq "" (
    set "list=!list:~1!"
    echo !list!
)
