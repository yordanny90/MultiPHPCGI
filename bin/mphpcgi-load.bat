@echo off
set "EXISTS=0"
for %%P in ("%PATH:;=";"%") do (
    if /i "%%~P"=="%~dp0" set "EXISTS=1"
)
if %EXISTS%==0 (
    set "PATH=%~dp0;%PATH%"
)