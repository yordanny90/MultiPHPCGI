@echo off
"%~dp0bin/nginx/1.26.0/nginx.exe" -t
if "%1"=="" (pause)