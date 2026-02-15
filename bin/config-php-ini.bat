@echo off
setlocal EnableDelayedExpansion
set "php_ver=%~1"
if "%php_ver%"=="" (
	echo Debe indicar una version de PHP para configurar>&2
	exit /b 1
)
set f=0
if "%~2"=="/f" set f=1
set "php_dir=%~dp0..\php\%php_ver%"
set "php_ini=%php_dir%\php.ini"
set "php_exe=%php_dir%\php.exe"

if %f%==1 goto make
if exist "%php_ini%" (
    echo La configuracion de php.ini ya existe>&2
    exit /b 0
)

:make
echo Modificando php.ini %php_ver%...
set "ini_tmp=%php_ini%.tmp"
copy /Y "%php_dir%\php.ini-production" "%ini_tmp%"
if %ERRORLEVEL% neq 0 (
    echo Error: No se pudo crear el nuevo php.ini>&2
    exit /b %ERRORLEVEL%
)
echo Agregando xdebug.ini
echo.>>"%ini_tmp%"
type "%~dp0..\inc\xdebug.ini">>"%ini_tmp%"
powershell -Command "(Get-Content '%ini_tmp%') -replace '^;\s*(opcache\.enable\s*=|cgi.fix_pathinfo\s*=|extension_dir\s*=\s*\"ext\")', '$1' | Set-Content '%ini_tmp%'"
if %ERRORLEVEL% neq 0 (
	echo Error: No se pudo configurar el php.ini>&2
	exit /b %ERRORLEVEL%
)

call "%~dp0test-phpini.bat" "%php_ver%" "%ini_tmp%"
if %ERRORLEVEL% neq 0 (
	exit /b %ERRORLEVEL%
)

echo.>"%php_ini%.bk"
if exist "%php_ini%" (
    copy /Y "%php_ini%" "%php_ini%.bk"
)
copy /Y "%ini_tmp%" "%php_ini%"
if %ERRORLEVEL% neq 0 (
	echo Error: No se pudo guardar el php.ini>&2
	exit /b %ERRORLEVEL%
)
del "%ini_tmp%"
set "extensions=opcache,sodium,ffi,bz2,intl,ldap,ftp,gd,gd2,gettext,curl,fileinfo,gmp,imap,mbstring,exif,openssl,mysqli,odbc,pgsql,pdo_mysql,pdo_odbc,pdo_pgsql,pdo_sqlite,sqlite3,soap,sockets,tidy,xsl,zip,shmop,xmlrpc"
set Habilitando extensiones: %extensions%
for %%e in (%extensions%) do (
    call "%~dp0extphp-isnul.bat" "%php_ini%" "%%~e"2>nul
    if !ERRORLEVEL! neq 0 (
        echo Extension %%~e preexistente
    ) else (
        call "%~dp0extphp-set.bat" "%php_ver%" "%php_ini%" "%%~e"
    )
)
exit /b 0
