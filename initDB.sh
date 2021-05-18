#!/bin/bash

DEBIAN_FRONTEND=noninteractive


while ! mysqladmin ping -h $MYSQL_HOST -u $MYSQL_USER -p$MYSQL_PASSWORD; do
        sleep 10
        echo "Waitting for....."
done
check=$(mysql -h $MYSQL_HOST -u $MYSQL_USER -p$MYSQL_PASSWORD -e "use $MYSQL_DATABASE; show tables;" | grep "userinfo")

if [ "$check" != "userinfo" ]; then
        echo "Init DB"
	mysql -h $MYSQL_HOST -u $MYSQL_USER -p$MYSQL_PASSWORD $MYSQL_DATABASE <  /etc/freeradius/3.0/mods-config/sql/main/mysql/schema.sql
	mysql -h $MYSQL_HOST -u $MYSQL_USER -p$MYSQL_PASSWORD $MYSQL_DATABASE < /var/www/html/daloradius/contrib/db/fr2-mysql-daloradius-and-freeradius.sql
	mysql -h $MYSQL_HOST -u $MYSQL_USER -p$MYSQL_PASSWORD $MYSQL_DATABASE < /var/www/html/daloradius/contrib/db/mysql-daloradius.sql

else
        echo "Not InitDB"
fi



# Enable SQL in freeradius

sed -i 's|driver = "rlm_sql_null"|driver = "rlm_sql_mysql"|' /etc/freeradius/3.0/mods-available/sql 
sed -i 's|dialect = "sqlite"|dialect = "mysql"|' /etc/freeradius/3.0/mods-available/sql
sed -i 's|driver = "rlm_sql_null"|driver = "rlm_sql_mysql"|' /etc/freeradius/3.0/mods-available/sql 
sed -i 's|dialect = "sqlite"|dialect = "mysql"|' /etc/freeradius/3.0/mods-available/sql
sed -i 's|dialect = ${modules.sql.dialect}|dialect = "mysql"|' /etc/freeradius/3.0/mods-available/sqlcounter # avoid instantiation error
sed -i 's|ca_file = "/etc/ssl/certs/my_ca.crt"|#ca_file = "/etc/ssl/certs/my_ca.crt"|' /etc/freeradius/3.0/mods-available/sql #disable sql encryption
sed -i 's|certificate_file = "/etc/ssl/certs/private/client.crt"|#certificate_file = "/etc/ssl/certs/private/client.crt"|' /etc/freeradius/3.0/mods-available/sql #disable sql encryption
sed -i 's|private_key_file = "/etc/ssl/certs/private/client.key"|#private_key_file = "/etc/ssl/certs/private/client.key"|' /etc/freeradius/3.0/mods-available/sql #disable sql encryption
sed -i 's|tls_required = yes|tls_required = no|' /etc/freeradius/3.0/mods-available/sql #disable sql encryption
sed -i 's|#\s*read_clients = yes|read_clients = yes|' /etc/freeradius/3.0/mods-available/sql 




# Set Database connection
sed -i 's|^#\s*server = .*|server = "'$MYSQL_HOST'"|' /etc/freeradius/3.0/mods-available/sql
sed -i 's|^#\s*port = .*|port = "'$MYSQL_PORT'"|' /etc/freeradius/3.0/mods-available/sql
sed -i '1,$s/radius_db.*/radius_db="'$MYSQL_DATABASE'"/g' /etc/freeradius/3.0/mods-available/sql
sed -i 's|^#\s*password = .*|password = "'$MYSQL_PASSWORD'"|' /etc/freeradius/3.0/mods-available/sql 
sed -i 's|^#\s*login = .*|login = "'$MYSQL_USER'"|' /etc/freeradius/3.0/mods-available/sql

ln -s /etc/freeradius/3.0/mods-available/sqlcounter /etc/freeradius/3.0/mods-enabled/sqlcounter

sed -i "s/\$configValues\['CONFIG_DB_HOST'\] = .*;/\$configValues\['CONFIG_DB_HOST'\] = '$MYSQL_HOST';/" /var/www/html/daloradius/library/daloradius.conf.php
sed -i "s/\$configValues\['CONFIG_DB_PORT'\] = .*;/\$configValues\['CONFIG_DB_PORT'\] = '$MYSQL_PORT';/" /var/www/html/daloradius/library/daloradius.conf.php
sed -i "s/\$configValues\['CONFIG_DB_PASS'\] = .*;/\$configValues\['CONFIG_DB_PASS'\] = '$MYSQL_PASSWORD';/" /var/www/html/daloradius/library/daloradius.conf.php 
sed -i "s/\$configValues\['CONFIG_DB_USER'\] = .*;/\$configValues\['CONFIG_DB_USER'\] = '$MYSQL_USER';/" /var/www/html/daloradius/library/daloradius.conf.php
sed -i "s/\$configValues\['CONFIG_DB_NAME'\] = .*;/\$configValues\['CONFIG_DB_NAME'\] = '$MYSQL_DATABASE';/" /var/www/html/daloradius/library/daloradius.conf.php
sed -i "s/\$configValues\['FREERADIUS_VERSION'\] = .*;/\$configValues\['FREERADIUS_VERSION'\] = '3';/" /var/www/html/daloradius/library/daloradius.conf.php

echo "Succecfully !"

supervisord -c /etc/supervisor.conf
