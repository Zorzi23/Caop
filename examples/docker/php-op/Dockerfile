FROM php:8.2-cli

# Instala dependências do sistema e extensões do PostgreSQL
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    && docker-php-ext-install pgsql pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# Instala Xdebug e OpenTelemetry
RUN pecl install xdebug-3.3.1 && docker-php-ext-enable xdebug
RUN pecl install opentelemetry-1.1.3 && docker-php-ext-enable opentelemetry

# Verifica as extensões instaladas
RUN php -m | grep -E 'pdo_pgsql|pgsql'

WORKDIR /var/www/html
CMD ["php", "-S", "0.0.0.0:8080"]
