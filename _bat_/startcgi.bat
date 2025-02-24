@echo off
setlocal
IF "%1"=="" (
echo Parametros:
echo   Version: 7.2/7.4/8.1/...
echo   IP: 127.0.0.1
echo   Puerto inicial: 9900
echo   Puerto final: 9905
echo.
echo Ejemplo para iniciar 6 puertos de PHP 8.1:
echo   "%~0" 8.1 127.0.0.1 9990 9995
goto end
)
set phpdir=%~dp0bin\php\%1
set phpcgi=%phpdir%\php-cgi.exe
echo %phpcgi%
set php=%phpdir%\php.exe
IF NOT exist "%phpdir%\php-cgi.exe" (
echo No existe la version "%1" de PHP
goto end
)
"%phpdir%\php-cgi.exe" -v
echo.
"%phpdir%\php.exe" --ini
set /a pI=0
set /a pI=%3
set /a pF=0
set /a pF=%4
IF 1 GTR %pI% (
echo.
echo Debe indicar el puerto inicial
goto end
)
IF 1 GTR %pF% (
echo.
echo Debe indicar el puerto final
goto end
)
IF %pI% GTR %pF% (
echo.
echo El puerto final debe ser mayor al inicial
goto end
)
set /a max=128
set /a puertos=%pF%-%pI%
IF %puertos% GTR %max% (
echo.
echo El maximo de puertos es %max%
goto end
)
echo.
echo.
echo Iniciando servicios PHP %1
endlocal
setlocal
set phpdir=%~dp0bin\%1
set path=%phpdir%;%path%
for /L %%p in (%3,1,%4) do (
	echo Nuevo php-cgi %2:%%p
	"%~dp0hidec.exe" "%phpdir%\php-cgi.exe" -b %2:%%p
	rem call "%~dp0startcgi_unit.bat" "%phpdir%\php-cgi.exe" %2:%%p
)
echo.
echo Completado!
:end
endlocal