@echo off
setlocal
call "%~dp0..\bin\utils.bat"
set name=%1
if "%name%"=="" (
	set name=localhost
)
set dir=%~dp0..\
set tmp=%dir%tmp\
@mkdir "%tmp%"
set sslconf=%dir%conf\ssl\openssl.conf
%openssl% genpkey -algorithm RSA -out "%tmp%\private.key"
if %ERRORLEVEL% neq 0 (
	echo Error de openssl
	exit /b %ERRORLEVEL%
)
%openssl% req -new -key "%tmp%\private.key" -out "%tmp%\request.csr" -config "%sslconf%"
if %ERRORLEVEL% neq 0 (
	echo Error de openssl
	exit /b %ERRORLEVEL%
)
%openssl% x509 -req -days 3650 -in "%tmp%\request.csr" -signkey "%tmp%\private.key" -out "%tmp%\certificate.crt" -extensions v3_ca -extfile "%sslconf%"
if %ERRORLEVEL% neq 0 (
	echo Error de openssl
	exit /b %ERRORLEVEL%
)
%openssl% x509 -in "%tmp%\certificate.crt" -out "%tmp%\certificate.pem" -outform PEM
if %ERRORLEVEL% neq 0 (
	echo Error de openssl
	exit /b %ERRORLEVEL%
)
@echo Verificando certificados
%openssl% rsa -in "%tmp%\private.key" -check
if %ERRORLEVEL% neq 0 (
	echo Error de openssl
	exit /b %ERRORLEVEL%
)
%openssl% req -in "%tmp%\request.csr" -noout -text -config "%sslconf%"
if %ERRORLEVEL% neq 0 (
	echo Error de openssl
	exit /b %ERRORLEVEL%
)
%openssl% x509 -in "%tmp%\certificate.crt" -noout -text
if %ERRORLEVEL% neq 0 (
	echo Error de openssl
	exit /b %ERRORLEVEL%
)
set certdir=%dir%inc\ssl\
@mkdir "%certdir%"
copy /Y "%tmp%private.key" "%certdir%%name%.key"
del "%tmp%private.key"
copy /Y "%tmp%request.csr" "%certdir%%name%.csr"
del "%tmp%request.csr"
copy /Y "%tmp%certificate.crt" "%certdir%%name%.crt"
del "%tmp%certificate.crt"
copy /Y "%tmp%certificate.pem" "%certdir%%name%.pem"
del "%tmp%certificate.pem"
start "" "%certdir%%name%.crt"