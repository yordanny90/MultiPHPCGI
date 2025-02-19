@echo off
setlocal
call "%~dp0utils.bat"
set "nginx_ver=%1"
set "nginx_dir=%~dp0nginx\%nginx_ver%"
set "nginx_exe=%nginx_dir%\nginx.exe"
set "tmp=%~dp0..\tmp"
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
	for /f %%a in ('%%curl%% -I -s -w "%%{http_code}" "%%url%%%%zipfile%%"') do if "%%a"=="200" (goto found)
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

rmdir /s /q "%tmp%\%name%\"
@mkdir "%tmp%\%name%\"
%curl% -s -o "%tmp%\%name%\%zipfile%" "%url%%zipfile%"
if %ERRORLEVEL% neq 0 (
	echo Error: No se pudo descargar el archivo.
	exit /b %ERRORLEVEL%
)
if not exist "%tmp%\%name%\%zipfile%" (
	rmdir /s /q "%tmp%\%name%"
	echo Error: No se pudo descargar el archivo.
	exit /b 1
)

echo Descomprimiendo ZIP...
%zip7% x "%tmp%\%name%\%zipfile%" "-o%tmp%\%name%\"
if %ERRORLEVEL% neq 0 (
	rmdir /s /q "%tmp%\%name%"
	echo Error: No se pudo descomprimir el archivo.
	exit /b %ERRORLEVEL%
)

if not exist "%tmp%\%name%\%name%\nginx.exe" (
	rmdir /s /q "%tmp%\%name%"
	echo Error: Archivo de instalacion invalido.
	exit /b 1
)

@mkdir "%nginx_dir%"
xcopy "%tmp%\%name%\%name%\" "%nginx_dir%\" /e /h /i /y
if %ERRORLEVEL% neq 0 (
	rmdir /s /q "%tmp%\%name%"
	echo Error: No se pudo copiar la carpeta de NGINX.
	exit /b %ERRORLEVEL%
)

echo Eliminando archivos temporales...
rmdir /s /q "%tmp%\%name%"

if not exist "%nginx_dir%\nginx.exe" (
	echo Error: No se pudo completar la instalacion.
	exit /b 1
)
