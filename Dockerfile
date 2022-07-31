FROM php:8.0-fpm

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
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd sockets

# Soporte para jpeg GD ext
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd


ENV PHP_MEMORY_LIMIT=2G\
    UPLOAD_MAX_FILESIZE=256M\
    POST_MAX_SIZE=256M

RUN echo 'memory_limit = -1' >> /usr/local/etc/php/conf.d/docker-php-memlimit.ini;

# Instalamos dentro de la imagen la última versión de composer, para ello copiamos la imagen disponible en el repositorio:
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# # Copiamos de la última imagen de node en nuestro proyecto las librerías de los módulos y de node
# COPY --from=node:latest /usr/local/lib/node_modules /usr/local/lib/node_modules
# COPY --from=node:latest /usr/local/bin/node /usr/local/bin/node
# # Creamos un enlace virtual para poder utilizar directamente npm dentro de la máquina Docker:
# RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm


# Creamos un usuario de sistema para ejecutar los comando Composer y Artisan:
# RUN useradd -G www-data,root -u $uid -d /home/$user $user
# RUN mkdir -p /home/$user/.composer &amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;&amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp;amp; \
#     chown -R $user:$user /home/$user

RUN echo 'alias artisan="php /var/www/artisan"' >> ~/.bashrc
RUN echo 'alias fresh-db="bash /var/www/docker/scripts/fresh-db.sh"' >> ~/.bashrc

# Definimos el directorio de trabajo dentro de nuestra imagen
WORKDIR /var/www

COPY --chown=www-data:www-data . .

EXPOSE 9000

