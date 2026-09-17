FROM php:8.2-apache

# Cài driver kết nối MySQL cho PHP
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copy toàn bộ code của project vào thư mục web root của Apache
COPY . /var/www/html/

# Render sẽ tự nhận cổng này để expose ra internet
EXPOSE 80
