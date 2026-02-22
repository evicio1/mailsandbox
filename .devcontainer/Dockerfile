FROM php:8.2-apache-bookworm

# Install MariaDB client and required packages for IMAP
RUN apt-get update && export DEBIAN_FRONTEND=noninteractive \
    && apt-get install -y default-mysql-client libc-client-dev libkrb5-dev wget curl procps \
    && apt-get clean -y && rm -rf /var/lib/apt/lists/*

# Install php extensions (pdo, mysqli)
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Install IMAP extension
RUN docker-php-ext-configure imap --with-kerberos --with-imap-ssl \
    && docker-php-ext-install imap

# Enable Apache mod_rewrite (needed for clean URLs / .htaccess routing)
RUN a2enmod rewrite

# Copy entrypoint script that sets APACHE_DOCUMENT_ROOT at runtime
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
