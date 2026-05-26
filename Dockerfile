FROM php:8.2-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev libcurl4-openssl-dev ca-certificates libzip-dev \
    && docker-php-ext-install pdo_mysql pdo_pgsql pgsql curl zip \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app
COPY . /app

RUN mkdir -p /app/public/uploads \
    && chmod -R 777 /app/public/uploads

ENV PORT=8080
EXPOSE 8080

CMD ["sh", "-lc", "php -S 0.0.0.0:${PORT} -t /app /app/docker/router.php"]
