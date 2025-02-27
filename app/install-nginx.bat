@echo off
setlocal
call "%~dp0..\bin\utils.bat"
set "nginx_ver=%1"
set "nginx_dir=%~dp0..\bin\nginx\%nginx_ver%"
set "nginx_exe=%nginx_dir%\nginx.exe"
set "_tmp=%~dp0..\tmp"
set "nginx_conf=%~dp0..\conf\nginx"
set "nginx_inc=%~dp0..\inc\nginx"
if "%nginx_ver%"=="" (
	echo Debe indicar una version de NGINX para instalar.
	exit /b 1
)

echo Instalacion de NGINX version %nginx_ver%
if exist "%nginx_exe%" (
	echo La instalacion ya existe.
	exit /b 0
)

set "url=https://nginx.org/download/"
set "nginxbase=%nginx_ver:~0,2%"
if "%nginxbase%"=="1." (
	set /p name=<(echo nginx-%nginx_ver%)
	set "name=nginx-%nginx_ver%"
	set "zipfile=%name%.zip"
	echo %url%%name%
	for /f %%a in ('curl.exe -I -s -w "%%{http_code}" "%%url%%%%zipfile%%"') do if "%%a"=="200" (goto found)
	set "name="
) else (
	echo Error: Version de NGINX invalida.
	exit /b 1
)
:found
if "%name%"=="" (
	echo Error: Version de NGINX no encontrada.
	exit /b 2
)

rmdir /s /q "%_tmp%\%name%\"
@mkdir "%_tmp%\%name%\"
curl.exe -s -o "%_tmp%\%name%\%zipfile%" "%url%%zipfile%"
if %ERRORLEVEL% neq 0 (
	echo Error: No se pudo descargar el archivo.
	exit /b %ERRORLEVEL%
)
if not exist "%_tmp%\%name%\%zipfile%" (
	rmdir /s /q "%_tmp%\%name%"
	echo Error: No se pudo descargar el archivo.
	exit /b 1
)

echo Descomprimiendo ZIP...
7za.exe x "%_tmp%\%name%\%zipfile%" "-o%_tmp%\%name%\"
if %ERRORLEVEL% neq 0 (
	rmdir /s /q "%_tmp%\%name%"
	echo Error: No se pudo descomprimir el archivo.
	exit /b %ERRORLEVEL%
)

if not exist "%_tmp%\%name%\%name%\nginx.exe" (
	rmdir /s /q "%_tmp%\%name%"
	echo Error: Archivo de instalacion invalido.
	exit /b 1
)

if not exist "%nginx_dir%" (
	@mkdir "%nginx_dir%"
)
xcopy "%_tmp%\%name%\%name%\" "%nginx_dir%\" /e /h /i /y /j
if %ERRORLEVEL% neq 0 (
	rmdir /s /q "%_tmp%\%name%"
	echo Error: No se pudo copiar la carpeta de NGINX.
	exit /b %ERRORLEVEL%
)

echo Eliminando archivos temporales...
rmdir /s /q "%_tmp%\%name%"

xcopy "%nginx_dir%\conf" "%nginx_conf%\conf" /e /h /i /y /j
if %ERRORLEVEL% neq 0 (
	rmdir /s /q "%_tmp%\%name%"
	echo Error: No se pudo copiar la carpeta de NGINX.
	exit /b %ERRORLEVEL%
)
xcopy "%nginx_dir%\logs" "%nginx_conf%\logs" /e /h /i /y /j
if %ERRORLEVEL% neq 0 (
	rmdir /s /q "%_tmp%\%name%"
	echo Error: No se pudo copiar la carpeta de NGINX.
	exit /b %ERRORLEVEL%
)
xcopy "%nginx_dir%\temp" "%nginx_conf%\temp" /e /h /i /y /j
if %ERRORLEVEL% neq 0 (
	rmdir /s /q "%_tmp%\%name%"
	echo Error: No se pudo copiar la carpeta de NGINX.
	exit /b %ERRORLEVEL%
)
xcopy "%nginx_inc%" "%nginx_conf%" /e /h /i /y /j
if %ERRORLEVEL% neq 0 (
	rmdir /s /q "%_tmp%\%name%"
	echo Error: No se pudo copiar la carpeta de NGINX.
	exit /b %ERRORLEVEL%
)

if not exist "%nginx_dir%\nginx.exe" (
	echo Error: No se pudo completar la instalacion.
	exit /b 1
)
