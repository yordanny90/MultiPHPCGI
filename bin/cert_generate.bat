@echo off
setlocal
net session >nul 2>&1
if %ERRORLEVEL% neq 0 (
	powershell -Command "Start-Process cmd -ArgumentList '/c \"%~fs0\" %*' -Verb RunAs"
	exit /b
)
echo Generando nuevo certificado...
call "%~dp0utils.bat"
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
openssl.exe genpkey -algorithm RSA -out "%_tmp%\private.key"
if %ERRORLEVEL% neq 0 (
	echo Error de openssl
	exit /b %ERRORLEVEL%
)
openssl.exe req -new -key "%_tmp%\private.key" -out "%_tmp%\request.csr" -config "%_tmp%openssl.conf"
if %ERRORLEVEL% neq 0 (
	echo Error de openssl
	exit /b %ERRORLEVEL%
)
openssl.exe x509 -req -days 3650 -in "%_tmp%\request.csr" -signkey "%_tmp%\private.key" -out "%_tmp%\certificate.crt" -extensions v3_ca -extfile "%_tmp%openssl.conf"
if %ERRORLEVEL% neq 0 (
	echo Error de openssl
	exit /b %ERRORLEVEL%
)

@echo Verificando certificados
openssl.exe rsa -in "%_tmp%\private.key" -check
if %ERRORLEVEL% neq 0 (
	echo Error de openssl
	exit /b %ERRORLEVEL%
)
openssl.exe req -in "%_tmp%\request.csr" -noout -text -config "%_tmp%openssl.conf"
if %ERRORLEVEL% neq 0 (
	echo Error de openssl
	exit /b %ERRORLEVEL%
)
openssl.exe x509 -in "%_tmp%\certificate.crt" -noout -text
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
@REM certutil -addstore -f "Root" "%certdir%%name%.crt"
if %ERRORLEVEL% neq 0 (
	pause
)
start "" "%certdir%%name%.crt"
