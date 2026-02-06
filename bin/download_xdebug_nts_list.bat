@echo off
setlocal
set "file=%~dp0..\tmp\xdebug_nts_list.txt"
set "ps=%~dp0tools\get_xdebug_nts_list.ps1"
call "%~dp0ps2file.bat" %ps% %file% %*