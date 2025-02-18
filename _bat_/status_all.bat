@echo off
echo Puertos de PHP
call "%~dp0php.bat" list
echo Puertos de PHP-CGI
call "%~dp0phpcgi.bat" list
echo Puertos de NGINX
call "%~dp0portlistnginx.bat"
if "%1"=="" (pause)