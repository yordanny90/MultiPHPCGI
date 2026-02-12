@echo off
for %%P in ("%PATH:;=";"%") do (
    if /i "%%~P"=="%~dp0" (
        goto found
    )
)
set "PATH=%~dp0;%PATH%"
exit /b 0

:found
exit /b 0