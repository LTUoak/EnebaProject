# Recipe for the API's container image. Render reads this file, builds the
# image on its own servers and runs it; Docker is not needed on your computer.

# Start from the official image that already contains PHP 8.4 and the Apache web server.
FROM php:8.4-apache

# Add the PostgreSQL driver PHP needs to talk to Neon (it is not included by default).
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# Use PHP's production settings: errors go to the server log, never to the visitor.
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Render tells the container which port to listen on through the PORT variable
# (10000 unless changed). Make Apache listen there instead of its default, 80.
ENV PORT=10000
RUN sed -i 's/^Listen 80$/Listen ${PORT}/' /etc/apache2/ports.conf

# Site settings: serve backend/public and send every address to index.php.
COPY docker/vhost.conf /etc/apache2/sites-available/000-default.conf

# Copy the API code into the image. backend/.env is deliberately not copied
# (see .dockerignore): on Render the settings come from its Environment page.
COPY public/ /var/www/html/public/
COPY src/ /var/www/html/src/

EXPOSE 10000
