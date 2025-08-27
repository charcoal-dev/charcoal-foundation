FROM php:8.3-__SAPI_BASE__-bookworm AS base
ARG CHARCOAL_UID=1000
ARG CHARCOAL_GID=1000

# Create user/group for SAPI (match host IDs)
RUN groupadd -g ${CHARCOAL_GID} charcoal \
 && useradd -m -u ${CHARCOAL_UID} -g ${CHARCOAL_GID} -s /bin/bash charcoal

# SAPI Environment Setup
RUN apt-get update \
 && apt-get install -y --no-install-recommends supervisor __DEPS__ git unzip ca-certificates \
 && rm -rf /var/lib/apt/lists/*

COPY dev/docker/sapi/__SAPI_BASE__/ /opt/sapi/base
COPY dev/docker/sapi/app/__SAPI_ID__/ /opt/sapi/override
RUN set -e; \
  for d in /opt/sapi/base /opt/sapi/override; do \
    [ -f "$d/entrypoint.sh" ]    && cp -f "$d/entrypoint.sh" /entrypoint.sh && chmod +x /entrypoint.sh; \
    [ -f "$d/supervisord.conf" ] && cp -f "$d/supervisord.conf" /etc/supervisord.conf; \
    [ -f "$d/nginx.conf" ]       && mkdir -p /etc/nginx && cp -f "$d/nginx.conf" /etc/nginx/nginx.template.conf; \
    [ -f "$d/php8.3-fpm.conf" ]  && mkdir -p /usr/local/etc/php-fpm.d && cp -f "$d/php8.3-fpm.conf" /usr/local/etc/php-fpm.d/zz-charcoal.conf; \
  done; \
  rm -rf /opt/sapi

USER charcoal
WORKDIR /home/charcoal
RUN install -d -m 0750 /home/charcoal/{log,tmp,shared,storage}

# Preinstall composer repositories:
FROM base AS builder
USER root
RUN apt-get update && apt-get install -y --no-install-recommends curl \
 && rm -rf /var/lib/apt/lists/*
RUN curl -sS https://getcomposer.org/installer | php \
 && mv composer.phar /usr/local/bin/composer

USER charcoal
WORKDIR /home/charcoal/sandbox
COPY dev/composer.json dev/composer.lock ./
RUN composer install --no-dev --prefer-dist -o --no-interaction

# App Environment: DEV
FROM base AS dev
COPY --from=builder /usr/local/bin/composer /usr/local/bin/composer
USER charcoal
WORKDIR /home/charcoal
ENTRYPOINT ["/entrypoint.sh"]

# App Environment: PROD
FROM base AS prod
USER charcoal
WORKDIR /home/charcoal
COPY config/ config/
COPY app/sapi/__SAPI_ID__/ app/sapi/__SAPI_ID__/
COPY app/domain/ app/domain/
COPY app/shared/ app/shared/
COPY interfaces/__SAPI_ID__/ __SAPI_ID__/
COPY --from=builder /home/charcoal/sandbox/vendor/ /home/charcoal/vendor/
ENTRYPOINT ["/entrypoint.sh"]
