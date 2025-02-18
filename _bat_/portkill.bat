@echo off
setlocal
set host=%1
if "%host%"=="" (
	echo Parametros:
	echo   IP: 127.0.0.1 Usa * para todas las IP
	echo   Puerto inicial: 9990
	echo   Puerto final: 9995
	echo.
	echo Ejemplo para detener los puertos del 9990 al 9995 de la IP 127.0.0.1
	echo   "%~0" 127.0.0.1 9990 9995
	goto end
)

set pI=0
set pF=0
set /a pI=%2
set /a pF=%3
if %pI% LSS 1 (
	echo Debe indicar un puerto inicial mayor a cero
	goto end
)
if %pF% LSS 1 (
	echo Debe indicar un puerto final mayor a cero
	goto end
)
REM Recorrido de procesos y puertos
set /a cProc=0
set im=php-cgi.exe
for /f "tokens=1,2,3,4,5,6" %%a in ('call "%~dp0portim.bat" "%im%" l ^| findstr /I "%im%"') do (
	if %%d GEQ %pI% (
		if %%d LEQ %pF% (
			if "%host%"=="%%c" (
				echo Kill PID %%e	%%f	%%b
				taskkill /F /PID %%e
				set /a cProc+=1
			)
			if "%host%"=="*" (
				echo Kill PID %%e	%%f	%%b
				taskkill /F /PID %%e
				set /a cProc+=1
			)
		)
	)
)
echo Total Killed: %cProc%
:end
endlocal
exit /b