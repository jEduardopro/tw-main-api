FROM php:8.2-fpm

# Permitimos el paso de parámetros (argumentos) que se definirán en el fichero docker-compose.yml
# ARG user
# ARG uid

# Añadimos dependencias y utilidades interesantes al sistema como: git, curl, zip, ...:
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libxml2-dev \
    libonig-dev \
    libpng-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    zip \
    unzip

# Una vez finalizado borramos cache y limpiamos los archivos de instalación
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalamos las dependencias y extensiones PHP que necesitaremos en nuestro proyecto como: pdo_mysql o mbstring
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd sockets \
    && pecl install -o -f redis\
    && pecl install xdebug\
    && docker-php-ext-enable xdebug\
    # && pecl install grpc\
    # &&  rm -rf /tmp/pear \
    && docker-php-ext-enable redis; \
    pecl install imagick; \
    docker-php-ext-enable imagick; \
    true

# Soporte para jpeg GD ext
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd


ENV PHP_MEMORY_LIMIT=2G\
    UPLOAD_MAX_FILESIZE=256M\
    POST_MAX_SIZE=256M\
    XDEBUG_MODE=develop,debug,coverage

RUN echo 'memory_limit = -1' >> /usr/local/etc/php/conf.d/docker-php-memlimit.ini;

# Instalamos dentro de la imagen la última versión de composer, para ello copiamos la imagen disponible en el repositorio:
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Definimos el directorio de trabajo dentro de nuestra imagen
WORKDIR /var/www

COPY --chown=www-data:www-data . .

RUN composer install --optimize-autoloader --no-dev \
    && mkdir -p storage/logs \
    && php artisan optimize:clear \
    && chown -R www-data:www-data /var/www


RUN echo 'alias artisan="php /var/www/artisan"' >> ~/.bashrc
RUN echo 'alias fresh-db="bash /var/www/docker/scripts/fresh-db.sh"' >> ~/.bashrc


EXPOSE 9000

