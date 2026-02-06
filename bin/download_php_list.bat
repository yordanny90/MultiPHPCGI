@echo off
setlocal
set "file=%~dp0..\tmp\php_list.txt"
set "ps=%~dp0tools\get_php_list.ps1"
call "%~dp0ps2file.bat" %ps% %file% %*