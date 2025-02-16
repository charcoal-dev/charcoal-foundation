FROM ubuntu:24.04

ARG DEBIAN_FRONTEND=noninteractive
ARG HOST_UID=1000
ARG HOST_GID=1000
#ARG APP_INTERFACE_DIRECTORY

RUN if id ubuntu >/dev/null 2>&1; then \
        userdel -rf ubuntu || true; \
        getent group ubuntu && groupdel ubuntu || true; \
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
    mariadb-client \
    php8.3 \
    php8.3-cli \
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

COPY ./docker/containers/engine/supervisord.conf   /etc/supervisord.conf
COPY ./docker/containers/engine/entrypoint.sh      /root/entrypoint.sh

USER charcoal
WORKDIR /home/charcoal/
COPY ./config/ config/
COPY ./src/composer.json composer.json
COPY ./src/shared/ shared/
COPY ./src/domain/ domain/
#COPY ./src/interfaces/engine/ interfaces/engine/
COPY ./docker/containers/engine/charcoal.sh charcoal.sh
COPY ./docker/containers/engine/charcoal.php charcoal.php
COPY ./docker/containers/engine/build.php build.php

USER root
RUN chown -R charcoal:charcoal /home/charcoal/
RUN chmod +x /home/charcoal/charcoal.sh

USER root
WORKDIR /root
RUN chmod +x entrypoint.sh
ENTRYPOINT ["./entrypoint.sh"]
