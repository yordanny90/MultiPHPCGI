@echo off
call "%~dp0nginxstop.bat"
"%~dp0hidec.exe" "%~dp0bin/nginx/1.26.0/nginx.exe"