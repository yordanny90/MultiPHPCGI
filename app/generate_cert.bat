@echo off
setlocal
set name=%1
if "%name%"=="" (
	set name=localhost
)
set dir=%~dp0..\
set tmp=%dir%tmp\
@mkdir "%tmp%"
set sslconf=%dir%conf\ssl\openssl.conf
set PATH=%dir%bin\openssl;%PATH%
openssl version
if %ERRORLEVEL% neq 0 (
	echo Error de openssl
	goto end
)
openssl genpkey -algorithm RSA -out "%tmp%\private.key"
if %ERRORLEVEL% neq 0 (
	echo Error de openssl
	goto end
)
openssl req -new -key "%tmp%\private.key" -out "%tmp%\request.csr" -config "%sslconf%"
if %ERRORLEVEL% neq 0 (
	echo Error de openssl
	goto end
)
openssl x509 -req -days 3650 -in "%tmp%\request.csr" -signkey "%tmp%\private.key" -out "%tmp%\certificate.crt" -extensions v3_ca -extfile "%sslconf%"
if %ERRORLEVEL% neq 0 (
	echo Error de openssl
	goto end
)
openssl x509 -in "%tmp%\certificate.crt" -out "%tmp%\certificate.pem" -outform PEM
if %ERRORLEVEL% neq 0 (
	echo Error de openssl
	goto end
)
@echo Verificando certificados
openssl rsa -in "%tmp%\private.key" -check
if %ERRORLEVEL% neq 0 (
	echo Error de openssl
	goto end
)
openssl req -in "%tmp%\request.csr" -noout -text -config "%sslconf%"
if %ERRORLEVEL% neq 0 (
	echo Error de openssl
	goto end
)
openssl x509 -in "%tmp%\certificate.crt" -noout -text
if %ERRORLEVEL% neq 0 (
	echo Error de openssl
	goto end
)
set certdir=%dir%inc\ssl\
@mkdir "%certdir%"
copy "%tmp%private.key" "%certdir%%name%.key"
del "%tmp%private.key"
copy "%tmp%request.csr" "%certdir%%name%.csr"
del "%tmp%request.csr"
copy "%tmp%certificate.crt" "%certdir%%name%.crt"
del "%tmp%certificate.crt"
copy "%tmp%certificate.pem" "%certdir%%name%.pem"
del "%tmp%certificate.pem"
:end
