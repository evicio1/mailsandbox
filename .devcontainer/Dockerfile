FROM php:8.4-apache-bookworm

# Install system dependencies (MariaDB client, IMAP libs, Node, git, etc.)
RUN apt-get update && export DEBIAN_FRONTEND=noninteractive \
    && apt-get install -y \
    default-mysql-client \
    libc-client-dev \
    libkrb5-dev \
    libssl-dev \
    wget curl procps git zip unzip \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean -y && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install bundled PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql pcntl bcmath

# PHP 8.4 removed imap from bundled extensions — install via PECL
# PHP_OPENSSL=yes is required so PECL links against OpenSSL properly
RUN PHP_OPENSSL=yes pecl install imap \
    && docker-php-ext-enable imap

# Enable Apache mod_rewrite (needed for Laravel routing via .htaccess)
RUN a2enmod rewrite

# Copy and register entrypoint
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
