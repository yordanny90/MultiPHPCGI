Estos archivos se extraen directamente en la carpeta de nginx,
en la misma ubicacion de nginx.exe

IMPORTANTE: Antes de reemplazar los archivos,
cree un respaldo la carpeta conf. Ya que sobrescribe
los archivos de configuración

En los archivos "conf/fastcgi_backend.conf" y "phpcgi.bat"
se establecen los puertos de php-cgi que utilizara nginx

En el archivo "conf/location_php.conf" se establece la carpeta raíz del servicio http