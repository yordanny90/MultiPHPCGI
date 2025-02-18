@echo off
setlocal
set phpdir=%~dp0app\phpdir.txt
set /p phpver=<"%phpdir%"
if "%phpver%" == "" (
	echo Debe indicar la carpeta de PHP en "%phpdir%"
	goto:end
)
if "%1"=="" (
	"%~dp0bin\hidec.exe" "%~dp0bin\php\%phpver%\php.exe" -f "%~dp0app\app.php"
) else (
	"%~dp0bin\php\%phpver%\php.exe" -f "%~dp0app\app.php" -- %*
)
:end