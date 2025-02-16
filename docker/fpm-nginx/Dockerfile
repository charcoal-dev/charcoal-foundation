FROM ubuntu:24.04

ARG DEBIAN_FRONTEND=noninteractive
ARG HOST_UID=1000
ARG HOST_GID=1000
ARG APP_INTERFACE_DIRECTORY

RUN if id ubuntu >/dev/null 2>&1; then \
        userdel -r ubuntu && groupdel ubuntu; \
    fi

RUN groupadd -g ${HOST_GID} charcoal \
 && useradd -m -u ${HOST_UID} -g ${HOST_GID} --shell /bin/bash charcoal

RUN apt-get update \
 && apt-get install -y --no-install-recommends \
    software-properties-common \
    curl \
    zip \
    git \
    supervisor \
    iputils-ping \
    gettext-base \
    nginx \
    php8.3 \
    php8.3-cli \
    php8.3-fpm \
    php8.3-pdo \
    php8.3-mysql \
    php8.3-bcmath \
    php8.3-gmp \
    php8.3-mbstring \
    php8.3-curl \
 && rm -rf /var/lib/apt/lists/*

WORKDIR /root
RUN curl -sS https://getcomposer.org/installer -o composer-setup.php \
 && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
 && rm composer-setup.php

RUN mkdir -p /run/php \
 && touch /run/php/php8.3-fpm.sock \
 && touch /run/php/php8.3-fpm.pid

COPY ./docker/fpm-nginx/nginx.conf         /etc/nginx/nginx.template.conf
COPY ./docker/fpm-nginx/supervisord.conf   /etc/supervisord.conf
COPY ./docker/fpm-nginx/entrypoint.sh      /root/entrypoint.sh
COPY ./docker/fpm-nginx/php8.3-fpm.conf    /etc/php/8.3/fpm/pool.d/www.conf

WORKDIR /etc/nginx
RUN rm -rf sites-available sites-enabled nginx.conf

USER charcoal
WORKDIR /home/charcoal/
COPY ./docker/*.yml docker-build/
COPY ./config/ config/
COPY ./src/composer.json composer.json
COPY ./src/shared/ shared/
COPY ./src/domain/ domain/
COPY ./src/interfaces/${APP_INTERFACE_DIRECTORY}/ interfaces/${APP_INTERFACE_DIRECTORY}/
COPY ./templates/ templates/
COPY ./public_html/ public_html/

USER root
RUN chown -R charcoal:charcoal /home/charcoal/ \
 && chmod +x /home/charcoal \
 && chmod +x /root/entrypoint.sh

WORKDIR /root
ENTRYPOINT ["./entrypoint.sh"]