#!/bin/bash
set -e

# Ensure directories exist
mkdir -p /var/www/html/songs/covers
mkdir -p /var/www/html/songs/.cache

# Set permissions for Apache user
chown -R www-data:www-data /var/www/html/songs
chmod -R 775 /var/www/html/songs

# Execute command provided to container
exec "$@"
