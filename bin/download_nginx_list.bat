@echo off
setlocal
set "file=%~dp0..\tmp\nginx_list.txt"
set "ps=%~dp0tools\get_nginx_list.ps1"

set f=0
set v=0
set h=0
for %%a in (%*) do (
    if /i "%%~a"=="-f" set f=1
    if /i "%%~a"=="-v" set v=1
    if /i "%%~a"=="-h" set h=1
)
if %h%==1 goto help
if not exist "%file%" set f=1
if %f%==1 call "%~dp0tools\ps2file.bat" "%ps%" "%file%"
if %v%==1 type "%file%"
if %v%==0 echo %file%
exit /b 0

:help
echo.Descarga el listado de NGINX/FREENGINX online.
echo.Opciones:
echo.  -h Esta informacion de ayuda
echo.  -f Forzar la descarga del listado
echo.  -v Imprime el resultado. Si no se indica imprime la ruta del archivo cache