@echo off
setlocal

set dirlog=%~dp0..\tmp\mail_log\
@mkdir "%dirlog%"
set email=%dirlog%temp.eml
:: Leer entrada estándar (correo) y escribirlo en un archivo
break > "%email%"  & rem Borrar contenido previo
more > "%email%"
:: Extraer asunto del correo (opcional)
for /f "tokens=2 delims=:" %%A in ('findstr /B "Subject:" "%email%"') do set "subject=%%A"

::msg * "Nuevo correo recibido '%subject%'. Presiona Aceptar para abrir la carpeta."
::start "" "%email%"