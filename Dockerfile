# ==============================================================================
# Aura Music Player - Production Dockerfile
# Stack: PHP 8.2 Apache + FFmpeg + Python 3 + yt-dlp + spotdl
# ==============================================================================

FROM php:8.2-apache

# Set maintainer and environment
LABEL maintainer="Aura Music"
ENV DEBIAN_FRONTEND=noninteractive
ENV PYTHONUNBUFFERED=1

# Install system dependencies, ffmpeg, python, and build libraries
RUN apt-get update && apt-get install -y --no-install-recommends \
    ffmpeg \
    python3 \
    python3-pip \
    python3-venv \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    libzip-dev \
    libcurl4-openssl-dev \
    unzip \
    curl \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        mbstring \
        fileinfo \
        curl \
        gd \
        zip \
        opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Python audio downloaders (yt-dlp, spotdl) with --break-system-packages (Debian Bookworm)
RUN pip3 install --no-cache-dir --break-system-packages yt-dlp spotdl imageio-ffmpeg

# Enable Apache modules
RUN a2enmod rewrite headers expires

# Copy custom PHP configuration
COPY docker/php.ini $PHP_INI_DIR/conf.d/custom-php.ini

# Set working directory
WORKDIR /var/www/html

# Copy application source code
COPY . /var/www/html/

# Copy and setup entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Expose HTTP port
EXPOSE 80

# Healthcheck
HEALTHCHECK --interval=30s --timeout=10s --start-period=10s --retries=3 \
  CMD curl -f http://localhost/api/scan.php || exit 1

# Set entrypoint and default command
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
