@echo off
setlocal
call "%~dp0_load.bat"
set "php_ver=%1"
set "php_dir=%~dp0..\php\%php_ver%"
set "php_exe=%php_dir%\php.exe"
if "%php_ver%"=="" (
	echo Debe indicar una version de PHP para configurar.
	exit /b 1
)

echo Instalacion de PHP version %php_ver%
if not exist "%php_exe%" (
	echo La instalacion no existe.
	exit /b 1
)

echo Modificando php.ini %php_ver%...
set "php_ini=%php_dir%\php.ini.tmp"
copy /Y "%php_dir%\php.ini-production" "%php_ini%"
powershell -Command "(Get-Content '%php_ini%') -replace '^;\s*(opcache\.enable\s*=|cgi.fix_pathinfo\s*=|extension_dir\s*=\s*\"ext\")', '$1' | Set-Content '%php_ini%'"
if %ERRORLEVEL% neq 0 (
	echo Error: No se pudo configurar el php.ini.
	exit /b %ERRORLEVEL%
)
powershell -Command "(Get-Content '%php_ini%') -replace '^;(extension=(php_)?(sodium|ffi|bz2|intl|ldap|ftp|gd|gd2|gettext|curl|fileinfo|gmp|imap|mbstring|exif|openssl|mysqli|odbc|pgsql|pdo_mysql|pdo_odbc|pdo_pgsql|pdo_sqlite|sqlite3|soap|sockets|zip|tidy|xsl)(.dll)?)\b', '$1' | Set-Content '%php_ini%'"
if %ERRORLEVEL% neq 0 (
	echo Error: No se pudo configurar el php.ini.
	exit /b %ERRORLEVEL%
)
powershell -Command "(Get-Content '%php_ini%') -replace '^;(zend_extension=(opcache)(.dll)?)\b', '$1' | Set-Content '%php_ini%'"
if %ERRORLEVEL% neq 0 (
	echo Error: No se pudo configurar el php.ini.
	exit /b %ERRORLEVEL%
)
for /f "tokens=*" %%i in ('powershell -Command "& { & '%php_exe%' -c '%php_ini%' -m 2>&1 | Select-String 'warning|error' }"') do (
	echo Error: Se detecto un warning o error al comprobar PHP.
	echo %%i
	exit /b 1
)
if %ERRORLEVEL% neq 0 (
	echo Error: Se detecto un warning o error en PHP.
	exit /b %ERRORLEVEL%
)
copy /Y "%php_ini%" "%php_dir%\php.ini"
if %ERRORLEVEL% neq 0 (
	echo Error: No se pudo guardar el php.ini.
	exit /b %ERRORLEVEL%
)
del "%php_ini%"
