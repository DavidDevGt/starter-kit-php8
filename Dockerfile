# =============================================================================
# Stage 1 — Composer dependencies (builder)
# =============================================================================
FROM composer:2.7 AS deps

WORKDIR /app

# Copy manifests first — layer cache is invalidated only when they change
COPY composer.json composer.lock ./

RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --ignore-platform-reqs

# Copy source and generate optimised autoloader
COPY . .

RUN composer dump-autoload \
        --optimize \
        --no-dev \
        --classmap-authoritative

# =============================================================================
# Stage 2 — Production image
# =============================================================================
FROM php:8.2-fpm-alpine AS production

LABEL org.opencontainers.image.title="SaaS Starter Kit" \
      org.opencontainers.image.description="PHP 8.2 FPM production image" \
      org.opencontainers.image.authors="DavidDevGt"

# ── System dependencies ───────────────────────────────────────────────────────
# Install runtime libs first, then build-time deps in a virtual package
# that gets removed after compilation — keeps the layer small.
RUN apk add --no-cache \
        libzip \
        icu-libs \
        oniguruma \
        libpng \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        libzip-dev \
        icu-dev \
        oniguruma-dev \
        libpng-dev \
        linux-headers \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        opcache \
        intl \
        zip \
        bcmath \
        mbstring \
        gd \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del --no-cache .build-deps \
    && rm -rf /tmp/pear /var/cache/apk/*

# ── PHP configuration ─────────────────────────────────────────────────────────
COPY docker/php/opcache.ini  /usr/local/etc/php/conf.d/opcache.ini
COPY docker/php/php.ini      /usr/local/etc/php/conf.d/zz-custom.ini
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/zz-docker.conf

# ── Application ───────────────────────────────────────────────────────────────
WORKDIR /var/www/html

# Copy vendor from the deps stage (compiled with no-dev)
COPY --from=deps --chown=www-data:www-data /app/vendor ./vendor

# Copy application source
COPY --chown=www-data:www-data . .

# Writable dirs for logs / uploads; everything else read-only at runtime
RUN mkdir -p storage/logs \
    && chown -R www-data:www-data storage \
    && chmod -R 775 storage

# ── Security: drop to non-root ────────────────────────────────────────────────
USER www-data

EXPOSE 9000

HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD php-fpm -t || exit 1

CMD ["php-fpm"]
