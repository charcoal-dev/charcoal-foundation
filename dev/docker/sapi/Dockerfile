FROM php:8.3-__SAPI_BASE__-bookworm AS base
ARG CHARCOAL_UID=1000
ARG CHARCOAL_GID=1000
ARG SAPI_BASE=__SAPI_BASE__
ARG REDIS_PECL_VER=6.1.0

# Create user/group for SAPI (match host IDs)
RUN groupadd -g ${CHARCOAL_GID} charcoal \
 && useradd -m -u ${CHARCOAL_UID} -g ${CHARCOAL_GID} -s /bin/bash charcoal

# Core tools + your extra deps (__DEPS__) + supervisor
RUN --mount=type=cache,target=/var/cache/apt \
    --mount=type=cache,target=/var/lib/apt \
    set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends supervisor __DEPS__ ca-certificates; \
    rm -rf /var/lib/apt/lists/*

# Build deps for PHP extensions (separate layer, cached)
RUN --mount=type=cache,target=/var/cache/apt \
    --mount=type=cache,target=/var/lib/apt \
    set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
      $PHPIZE_DEPS \
      curl \
      libgmp-dev \
      libzip-dev \
      libcurl4-openssl-dev \
      libsqlite3-dev \
      libonig-dev \
      zlib1g-dev; \
    rm -rf /var/lib/apt/lists/*

# PHP extensions — group 1 (highly stable)
RUN set -eux; \
    docker-php-ext-configure gmp; \
    docker-php-ext-install -j"$(nproc)" bcmath mbstring sockets gmp zip

# PHP extensions — group 2 (DB)
RUN set -eux; \
    docker-php-ext-install -j"$(nproc)" pdo_mysql pdo_sqlite

# PHP extensions — group 3 (sqlite3, curl) isolated for cache clarity
RUN set -eux; \
    docker-php-ext-install -j"$(nproc)" sqlite3; \
    docker-php-ext-install -j"$(nproc)" curl

# Optional CLI-only extension
RUN set -eux; \
    if [ "$SAPI_BASE" = "cli" ]; then docker-php-ext-install -j"$(nproc)" pcntl; fi

# Redis via PECL (pinned) with cache for pear/pecl artifacts
RUN --mount=type=cache,target=/tmp/pear \
    set -eux; \
    pecl install -o -f "redis-${REDIS_PECL_VER}"; \
    docker-php-ext-enable redis; \
    rm -rf /tmp/pear

# Apply SAPI overrides (entrypoint, supervisord.conf, nginx/php-fpm templates)
COPY dev/docker/sapi/__SAPI_BASE__/ /opt/sapi/base
COPY dev/docker/sapi/app/__SAPI_ID__/ /opt/sapi/override
RUN set -e; \
  for d in /opt/sapi/base /opt/sapi/override; do \
    [ -f "$d/entrypoint.sh" ]    && cp -f "$d/entrypoint.sh" /entrypoint.sh && chmod +x /entrypoint.sh; \
    [ -f "$d/supervisord.ini" ] && cp -f "$d/supervisord.ini" /etc/supervisord.conf; \
    [ -f "$d/nginx.conf" ]       && mkdir -p /etc/nginx && cp -f "$d/nginx.conf" /etc/nginx/nginx.template.conf; \
    [ -f "$d/php8.3-fpm.ini" ]  && mkdir -p /usr/local/etc/php-fpm.d && cp -f "$d/php8.3-fpm.ini" /usr/local/etc/php-fpm.d/zz-charcoal.conf; \
  done; \
  rm -rf /opt/sapi

# Global PHP logging: no display, log to stderr
RUN printf "display_errors=Off\nlog_errors=On\nerror_log=/proc/self/fd/2\n" \
  > /usr/local/etc/php/conf.d/99-logs.ini

# (Optional) remove build deps to slim image — do this AFTER ext installs to keep cache usefulness
RUN --mount=type=cache,target=/var/cache/apt \
    --mount=type=cache,target=/var/lib/apt \
    set -eux; \
    apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false $PHPIZE_DEPS; \
    rm -rf /var/lib/apt/lists/*

USER charcoal
WORKDIR /home/charcoal
RUN install -d -m 0750 /home/charcoal/var/log \
    /home/charcoal/var/tmp \
    /home/charcoal/var/tmp/semaphore \
    /home/charcoal/var/shared  \
    /home/charcoal/var/shared/semaphore  \
    /home/charcoal/var/storage

WORKDIR /home/charcoal/dev
COPY dev/bootstrap.php bootstrap.php

# Preinstall composer repositories:
FROM base AS builder
USER root
RUN apt-get update && apt-get install -y --no-install-recommends curl git unzip ca-certificates \
 && rm -rf /var/lib/apt/lists/*
RUN curl -sS https://getcomposer.org/installer | php \
 && mv composer.phar /usr/local/bin/composer

USER charcoal
WORKDIR /home/charcoal/dev/composer
COPY dev/composer/composer.json composer.json
#COPY dev/composer/composer.lock composer.lock
RUN composer install --no-dev --prefer-dist -o --no-interaction

# App Environment: DEV
FROM base AS dev
ENV CHARCOAL_DOCKER_ENV=dev
COPY --from=builder /usr/local/bin/composer /usr/local/bin/composer
USER charcoal
WORKDIR /home/charcoal
ENTRYPOINT ["/entrypoint.sh"]

# App Environment: PROD
FROM base AS prod
ENV CHARCOAL_DOCKER_ENV=prod
USER charcoal
WORKDIR /home/charcoal
COPY config/ config/
COPY app/sapi/__SAPI_ID__/ app/sapi/__SAPI_ID__/
COPY app/domain/ app/domain/
COPY app/shared/ app/shared/
COPY interfaces/__SAPI_ID__/ __SAPI_ID__/
COPY --from=builder /home/charcoal/dev/composer/vendor/ /home/charcoal/dev/composer/vendor/
ENTRYPOINT ["/entrypoint.sh"]
