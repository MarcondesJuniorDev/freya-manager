FROM php:8.4-fpm

# Diretório padrão da aplicação dentro do container
WORKDIR /var/www

# Instalação de dependências do sistema e bibliotecas para extensões
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    locales \
    zip \
    jpegoptim optipng pngquant gifsicle \
    vim \
    unzip \
    git \
    curl \
    libzip-dev \
    libonig-dev

# Limpeza de cache de pacotes para reduzir tamanho da imagem
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalação das extensões necessárias para o Laravel 13 (incluindo processamento de imagens e banco)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install pdo_mysql mbstring zip exif pcntl gd

# Instalação da extensão estável do Redis para as filas do Laravel
RUN pecl install redis && docker-php-ext-enable redis

# Injeta o Composer estável mais recente
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configuração de usuário seguro para evitar conflitos de permissão de escrita (Writable storage/cache)
RUN groupadd -g 1000 www && useradd -u 1000 -ms /bin/bash -g www www

USER www

EXPOSE 9000
CMD ["php-fpm"]
