@echo off
setlocal
REM Recorrido de procesos y puertos
if "%1"=="" (
	echo.Debe indicar un nombre de proceso
	goto end
)
if "%2" NEQ "l" (
	echo.PROTOCOL	LADDR	HOST	PORT	PID	NAME	STATE	FADDR
)
for /f "tokens=1,2" %%o in ('tasklist /FO TABLE /NH /FI "IMAGENAME eq %1" ^| findstr /I "%1"') do (
	for /f "tokens=1,2,3,4,5" %%a in ('netstat -ano ^| findstr /V "*:*" ^| findstr /R ":[0-9]"') do (
		if "%%e"=="%%p" (
			for /f "tokens=1,2,3 delims=:" %%h in ("%%b") do (
				if "%%j"=="" (
					echo.%%a	%%b	%%h	%%i	%%p	%%o	%%d	%%c
				)
				if "%%j" NEQ "" (
					for /f "tokens=1 delims=[" %%v in ("%%b") do (
						for /f "tokens=1,2 delims=]" %%h in ("%%v") do (
							if "%%i" NEQ "" (
								for /f "tokens=1 delims=:" %%i in ("%%i") do (
									echo.%%a	%%b	%%h	%%i	%%p	%%o	%%d	%%c
								)
							)
						)
					)
				)
			)
		)
	)
)
:end
endlocal