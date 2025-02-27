@echo off
setlocal
net session >nul 2>&1
if %errorLevel% neq 0 (
	powershell -Command "Start-Process cmd -ArgumentList '/c \"%~fs0\" %*' -Verb RunAs"
	exit /b
)
echo Generando nuevo certificado...
call "%~dp0..\bin\utils.bat"
set name=localhost
set dir=%~dp0..\
set tmp=%dir%tmp\openssl\
@mkdir "%tmp%"
set "%tmp%\openssl.conf"
copy "%dir%conf\ssl\openssl.conf" "%tmp%openssl.conf"
call "%~dp0cert_iplist.bat" "%tmp%openssl.conf"
if %ERRORLEVEL% neq 0 (
	echo Error de openssl
	exit /b %ERRORLEVEL%
)
%openssl% genpkey -algorithm RSA -out "%tmp%\private.key"
if %ERRORLEVEL% neq 0 (
	echo Error de openssl
	exit /b %ERRORLEVEL%
)
%openssl% req -new -key "%tmp%\private.key" -out "%tmp%\request.csr" -config "%tmp%openssl.conf"
if %ERRORLEVEL% neq 0 (
	echo Error de openssl
	exit /b %ERRORLEVEL%
)
%openssl% x509 -req -days 3650 -in "%tmp%\request.csr" -signkey "%tmp%\private.key" -out "%tmp%\certificate.crt" -extensions v3_ca -extfile "%tmp%openssl.conf"
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
%openssl% req -in "%tmp%\request.csr" -noout -text -config "%tmp%openssl.conf"
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
copy /Y "%tmp%certificate.crt" "%certdir%%name%.crt"
del "%tmp%certificate.crt"
copy /Y "%tmp%openssl.conf" "%certdir%%name%.conf"
del "%tmp%openssl.conf"
del "%tmp%request.csr"
echo Registrando certificado...
certutil -addstore -f "Root" "%certdir%%name%.crt"
if %ERRORLEVEL% neq 0 (
	pause
)
start "" "%certdir%%name%.crt"
