@echo off
setlocal enabledelayedexpansion
set "sslconf=%1"
set i=1
for /f "tokens=2 delims=:" %%A in ('ipconfig ^| findstr /R "IPv4 Address[^0-9: ]*:([.0-9]+)"') do (
	set /a i+=1
	echo IP.!i!=%%A>> %sslconf%
)