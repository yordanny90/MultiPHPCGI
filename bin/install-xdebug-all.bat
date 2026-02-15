@echo off
setlocal
for /f "tokens=*" %%i in ('"%~dp0mphpcgi.bat" php_list') do (
    call "%~dp0install-xdebug.bat" "%%~i" %*
)
pause
