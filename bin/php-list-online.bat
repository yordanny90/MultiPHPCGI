@echo off
setlocal enabledelayedexpansion
for /f "delims=" %%a in ('call "%~dp0download_php_nts_list.bat" 0') do (
	set "linea=%%a"
	set "linea=!linea:*/php-=!"
	for /f "tokens=1 delims=-" %%b in ("!linea!") do echo %%b
)