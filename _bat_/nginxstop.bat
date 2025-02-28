@echo off
echo Deteniendo procesos de nginx.exe
"%~dp0nginx.exe" -s stop
"%~dp0nginx.exe" -s quit