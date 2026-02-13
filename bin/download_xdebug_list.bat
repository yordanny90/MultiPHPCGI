@echo off
setlocal
set "file=%~dp0..\tmp\xdebug_list.txt"
set "ps=%~dp0tools\get_xdebug_list.ps1"

set f=0
set v=0
for %%a in (%*) do (
    if /i "%%~a"=="-f" set f=1
    if /i "%%~a"=="-v" set v=1
)
if not exist "%file%" set f=1
if %f%==1 call "%~dp0tools\ps2file.bat" "%ps%" "%file%"
if %v%==1 type "%file%"
if %v%==0 echo %file%