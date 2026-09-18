FROM php:8.2-apache

# Chỉ cần driver PDO MySQL (pdo đã có sẵn trong image)
RUN docker-php-ext-install pdo_mysql

# Cho Apache nghe theo biến PORT của Render (mặc định 80 khi chạy local)
ENV PORT=80
RUN sed -i 's/Listen 80/Listen ${PORT}/' /etc/apache2/ports.conf \
 && sed -i 's/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/' /etc/apache2/sites-available/000-default.conf

# Copy code vào web root
COPY . /var/www/html/

EXPOSE 80