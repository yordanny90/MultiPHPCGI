@echo off
echo Deteniendo procesos de nginx.exe
"%~dp0hidec.exe" "%~dp0bin/nginx/1.26.0/nginx.exe" -s stop
"%~dp0hidec.exe" "%~dp0bin/nginx/1.26.0/nginx.exe" -s quit