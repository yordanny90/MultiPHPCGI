rem @echo off
if "%1"=="stop" (
call "%~dp0portkill.bat" 127.0.0.99 9900 9907
)
if "%1"=="start" (
call "%~dp0portkill.bat" 127.0.0.99 9900 9907
call "%~dp0startcgi.bat" 8.1 127.0.0.99 9900 9907
)
if "%1"=="list" (
call "%~dp0portlistcgi.bat"
)