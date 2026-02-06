@echo off
setlocal
echo Generando nuevo certificado...
call "%~dp0mphpcgi-load.bat"
set name=localhost
set dir=%~dp0..\
set _tmp=%dir%tmp\openssl\
if not exist "%_tmp%" (
	mkdir "%_tmp%"
)
copy "%dir%inc\ssl\openssl.conf" "%_tmp%openssl.conf"
call "%~dp0cert_iplist.bat" "%_tmp%openssl.conf"
if %ERRORLEVEL% neq 0 (
	echo Error de openssl
	exit /b %ERRORLEVEL%
)
call openssl genpkey -algorithm RSA -out "%_tmp%\private.key"
if %ERRORLEVEL% neq 0 (
	echo Error de openssl
	exit /b %ERRORLEVEL%
)
call openssl req -new -key "%_tmp%\private.key" -out "%_tmp%\request.csr" -config "%_tmp%openssl.conf"
if %ERRORLEVEL% neq 0 (
	echo Error de openssl
	exit /b %ERRORLEVEL%
)
call openssl x509 -req -days 3650 -in "%_tmp%\request.csr" -signkey "%_tmp%\private.key" -out "%_tmp%\certificate.crt" -extensions v3_ca -extfile "%_tmp%openssl.conf"
if %ERRORLEVEL% neq 0 (
	echo Error de openssl
	exit /b %ERRORLEVEL%
)

@echo Verificando certificados
call openssl rsa -in "%_tmp%\private.key" -check
if %ERRORLEVEL% neq 0 (
	echo Error de openssl
	exit /b %ERRORLEVEL%
)
call openssl req -in "%_tmp%\request.csr" -noout -text -config "%_tmp%openssl.conf"
if %ERRORLEVEL% neq 0 (
	echo Error de openssl
	exit /b %ERRORLEVEL%
)
call openssl x509 -in "%_tmp%\certificate.crt" -noout -text
if %ERRORLEVEL% neq 0 (
	echo Error de openssl
	exit /b %ERRORLEVEL%
)
set certdir=%dir%conf\ssl\
@mkdir "%certdir%"
copy /Y "%_tmp%private.key" "%certdir%%name%.key"
del "%_tmp%private.key"
copy /Y "%_tmp%certificate.crt" "%certdir%%name%.crt"
del "%_tmp%certificate.crt"
copy /Y "%_tmp%openssl.conf" "%certdir%%name%.conf"
del "%_tmp%openssl.conf"
del "%_tmp%request.csr"
echo Registrando certificado...
net session >nul 2>&1
if %ERRORLEVEL% neq 0 (
	powershell -Command "Start-Process certutil -ArgumentList '-addstore -f \"Root\" \"%certdir%%name%.crt\"' -Verb RunAs"
) else (
	certutil -addstore -f "Root" "%certdir%%name%.crt"
)
if %ERRORLEVEL% neq 0 (
	start "" "%certdir%%name%.crt"
	exit /b %ERRORLEVEL%
)
