FROM ubuntu:20.04
ENV DEBIAN_FRONTEND=noninteractive


RUN apt-get update \
       && apt-get install --yes --no-install-recommends \
       apache2 mariadb-client composer curl unzip\
       php libapache2-mod-php php-gd php-common \
       php-mail php-mail-mime php-mysql php-pear php-db \
       php-mbstring php-xml php-curl wget software-properties-common \
       supervisor freeradius freeradius-mysql freeradius-utils \
       && rm -rf /var/lib/apt/lists/* \
       && mkdir -p /var/log/supervisor \
       && mkdir -p /etc/supervisor/conf.d
RUN curl -s "https://packagecloud.io/install/repositories/phalcon/stable/script.deb.sh" | /bin/bash
RUN ln -s /etc/freeradius/3.0/mods-available/sql /etc/freeradius/3.0/mods-enabled/


RUN wget https://github.com/lirantal/daloradius/archive/master.zip \
        && unzip master.zip \
        && rm -fr master.zip \
        && mv daloradius-master /var/www/html/daloradius


COPY ./supervisor-apache2.conf /etc/supervisor/conf.d/apache2.conf
COPY ./supervisor-freeradius.conf /etc/supervisor/conf.d/freeradius.conf
COPY ./freeradius/eap /etc/freeradius/3.0/mods-available/eap
COPY ./freeradius/mschap /etc/freeradius/3.0/mods-available/mschap
COPY ./freeradius/default /etc/freeradius/3.0/sites-available/default
COPY  ./supervisor.conf /etc/supervisor.conf
COPY  ./initDB.sh /opt/initDB.sh

RUN chgrp -h freerad /etc/freeradius/3.0/mods-available/sql \
       && chown -R freerad:freerad /etc/freeradius/3.0/mods-enabled/sql \
       && chown -R www-data:www-data /var/www/html/daloradius/ \
       && cp /var/www/html/daloradius/library/daloradius.conf.php.sample /var/www/html/daloradius/library/daloradius.conf.php \
       && chmod 664 /var/www/html/daloradius/library/daloradius.conf.php \
       && chmod +x /opt/initDB.sh \
       && a2enmod rewrite



EXPOSE 80
EXPOSE 443
EXPOSE 1812
EXPOSE 1813


CMD ["sh", "/opt/initDB.sh"]
