#!/bin/bash

# Pacotes necessários no sistema

apt-get update
apt-get install -fy curl php5 php5-curl php5-pgsql php5-gd php5-sqlite apache2 libapache2-mod-php5 memcached php5-memcache imagemagick git

# Ativa os módulos rewrite e headers do apache

a2enmod rewrite
a2enmod headers

# Permite que definições do .htaccess possam sobrepor definições do apache

sed -i 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

rm /var/www/html/index.html
git clone https://github.com/felipebagetti/robin /var/www/html/

# Permissões para o usuário do apache

chown www-data:www-data -R /var/www/html/

# Criação de diretórios vazios (não estão incluídos no repositório do github)

mkdir /var/www/html/tmp/session/
mkdir /var/www/html/tmp/cache/
mkdir /var/www/html/tmp/files/

cd /var/www/html/ ; php -f install.php

# Reinicia o serviço do apache

service apache2 restart
