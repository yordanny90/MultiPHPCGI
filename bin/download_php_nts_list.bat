@echo off
setlocal
set "file=%~dp0..\tmp\php_nts_list.txt"
set "ps=%~dp0tools\get_php_nts_list.ps1"
call "%~dp0ps2file.bat" %ps% %file% %*