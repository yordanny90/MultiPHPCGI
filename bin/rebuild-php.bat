@echo off
setlocal
call "%~dp0config-php-legacy.bat" %~1 /f
call "%~dp0config-php-ini.bat" %~1 /f
