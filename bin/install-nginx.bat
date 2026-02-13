@echo off
setlocal
call "%~dp0_load.bat"
set "nginx_ver=%~1"
set "nginx_dir=%~dp0..\nginx\%nginx_ver%"
set "nginx_exe=%nginx_dir%\nginx.exe"
set "_tmp=%~dp0..\tmp"
set "nginx_conf=%~dp0..\usr\conf-nginx-%nginx_ver%"
set "nginx_inc=%~dp0..\inc\nginx"
if "%nginx_ver%"=="" (
	echo Debe indicar una version de NGINX para instalar.
	exit /b 1
)

echo Instalacion de NGINX version %nginx_ver%
if exist "%nginx_exe%" (
	echo La instalacion ya existe.
	goto copy_conf
)

:install
echo Buscando NGINX %nginx_ver%...
set nginx_url=
set "name=nginx-%nginx_ver%"
for /F "usebackq delims=" %%a IN (`call "%~dp0download_nginx_list.bat" -v ^| find "/%name%.zip"`) do (
    SET "nginx_url=%%a"
    goto done
)
set "name=freenginx-%nginx_ver%"
for /F "usebackq delims=" %%a IN (`call "%~dp0download_nginx_list.bat" -v ^| find "/%name%.zip"`) do (
    SET "nginx_url=%%a"
    goto done
)
:done
for /f %%a in ('call curl -I -s -w "%%{http_code}" "%%nginx_url%%"') do (
	if "%%a"=="200" (
	    goto found
	)
)
echo Error: Version de NGINX no encontrada. >&2
exit /b 1

:found
echo Encontrado!
echo Descargando: %nginx_url%
rmdir /s /q "%_tmp%\%name%\"
@mkdir "%_tmp%\%name%\"
set "zipfile=%name%.zip"
curl -s -o "%_tmp%\%zipfile%" "%nginx_url%"
if %ERRORLEVEL% neq 0 (
	echo Error: No se pudo descargar el archivo.
	exit /b %ERRORLEVEL%
)
if not exist "%_tmp%\%zipfile%" (
	rmdir /s /q "%_tmp%\%name%"
	echo Error: No se pudo descargar el archivo.
	exit /b 1
)

echo Descomprimiendo ZIP...
call 7za x -y "%_tmp%\%zipfile%" "-o%_tmp%\%name%\"
if %ERRORLEVEL% neq 0 (
	rmdir /s /q "%_tmp%\%name%"
	echo Error: No se pudo descomprimir el archivo.
	exit /b %ERRORLEVEL%
)
del "%_tmp%\%zipfile%"
if exist "%_tmp%\%name%\%name%\nginx.exe" (
    goto valid_zip
)
rmdir /s /q "%_tmp%\%name%"
echo Error: Archivo de instalacion invalido.
exit /b 1

:valid_zip
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

:copy_conf
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

call "%~dp0mphpcgi.bat" init-servers