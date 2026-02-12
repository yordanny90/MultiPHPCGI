@echo off
setlocal
set "file=%~dp0..\tmp\nginx_list.txt"
set "ps=%~dp0tools\get_nginx_list.ps1"
call "%~dp0ps2file.bat" %ps% %file% %*
echo %file%