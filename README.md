# PROJECT_NAME-dashboard

Pasos previos:

   1- Instalar MySQL
    
    sudo apt-get install mysql-server mysql-common mysql-client
    Se configurará un usuario con nombre y contraseña para poder acceder a MySQL
    
   2- Descargar phpMyAdmin.
   
    Se moverá el directorio a /var/www/html/
   
   3. Accedemos a phpMyAdmin desde http//localhost/phpmyadmin/
    
    En el menú de la izquierda se seleccionará "Nueva" y se le dará un nombre al nuevo esquema.
    
   4. Configurar el host virtual.
    
    En /etc/hosts/ añadir
        127.0.0.1       PROJECT_NAME.local
        
    En /etc/apache2/sites-anables configurar PROJECT_NAME.local
    
        <VirtualHost *:80>
	    ServerName PROJECT_NAME.local
	    ServerAlias */PROJECT_NAME
	    ServerAdmin webmaster@localhost
                    Options Indexes FollowSymLinks
                    AllowOverride All
                    Require all granted
            </Directory>
    	ErrorLog ${APACHE_LOG_DIR}/error.log
    	CustomLog ${APACHE_LOG_DIR}/access.log combined
        </VirtualHost>

    Dar permisos de apache
    
        sudo chgrp www-data public/.htaccess
        sudo chmod 775 public/.htaccess
	
    Activamos los modulos de php
    	
	sudo a2enmod headers
	sudo a2enmod rewrite

        
Para empezar a trabajar con un proyecto Symfony->

    cd projects/ 
    git clone...

    cd my-project/   
    composer install

 ·htaccess ->
 
    - Solamente eliminar la extensión del archivo creado.
    - Se encuentra en la carpeta public
 
 .env ->

    DATABASE_URL=mysql://db_user:db_password@127.0.0.1:3306/db_name

    - Siendo db_user, db_password y db_name el nombre de usuario y su contraseña de acceso
        junto con el nombre del esquema.
    - Variables CLIENT_ID y CLIENT_SECRET creados en el siguiente paso.

.phpunit.xml ->

    - Misma variable DATABASE_URL que en .env
    - Variables usadas en los tests de unidad
    - Variables CLIENT_ID y CLIENT_SECRET creados en el siguiente paso.

Siguientes pasos.Aplicar los comandos:

doctrine:schema:create ->

    - Se puede ejecutar facilemente desde el proyecto:
            click derecho en PROJECT_NAME-dashboard->symfony->run command.
    - Ahora ya tendremos montado el esquema en la base de datos.

php bin/console fos:oauth-server:create-client --redirect-uri="api/login" --grant-type="password"

    - Se ejecuta desde la consola
    - Creará un nuevo cliente del que tendremos que guardar el client_id y client_secret.
    - Estos id's se utilizarán tanto par alas peticiones en Postman para logearse, como en variables de entorno
    - Para utilizar el CLIENT_ID se deberá aplicar concatenando su id en la base de datos (1 por ejemplo),
        con "_" y después el client__id. ({id}_CLIENT_ID). Si cogemos el CLIENT_ID devuelto en la soncola, ya viene en el             formato correcto
# tuoferta-api
