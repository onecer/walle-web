FROM php:apache

RUN apt-get update && apt-get install -y gettext-base libicu-dev \
      zlib1g-dev unzip git subversion ssh ansible && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install bcmath intl mbstring mysqli opcache pdo_mysql
RUN a2enmod rewrite

COPY ./ /opt/walle-web
COPY docker/php.ini /usr/local/etc/php/conf.d/walle-web.ini
COPY docker/apache2.conf /etc/apache2/apache2.conf
COPY docker/entrypoint.sh /entrypoint.sh

WORKDIR /opt/walle-web
# RUN curl -sS https://getcomposer.org/installer | php \
#       && mv composer.phar /usr/local/bin/composer \
#       && chmod +x /usr/local/bin/composer
RUN curl -sS https://install.phpcomposer.com/installer | php \
      && mv composer.phar /usr/local/bin/composer \
      && chmod +x /usr/local/bin/composer
RUN composer config -g repo.packagist composer https://packagist.phpcomposer.com && composer config repo.packagist composer https://packagist.phpcomposer.com
RUN composer install
# --prefer-dist --no-dev --optimize-autoloader -vvvv
RUN chmod +x /entrypoint.sh && \
    chown -R www-data:www-data /opt/walle-web

EXPOSE 80
ENTRYPOINT ["/entrypoint.sh"]
CMD ["apache2-foreground"]
