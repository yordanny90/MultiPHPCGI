@echo off
setlocal
set "csc=C:\Windows\Microsoft.NET\Framework64\v4.0.30319\csc.exe"
if not exist "%csc%" (
    echo Debe instalar "%csc%" para compilar MultiPHPCGI
    exit /b 1
)
set "exe=%~dp0..\MultiPHPCGI.exe"
del "%exe%"
"%csc%" /t:winexe "/win32icon:%~dp0..\bin\app\favicon.ico" "/out:%exe%" "%~dp0TrayIcon.cs"