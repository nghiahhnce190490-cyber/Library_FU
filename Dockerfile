FROM php:8.2-apache

# Chỉ cần driver PDO MySQL (pdo đã có sẵn trong image)
RUN docker-php-ext-install pdo_mysql

# Cấu hình PHP cho môi trường chạy thật:
#  - không in lỗi chi tiết ra trình duyệt (tránh lộ đường dẫn, câu lệnh SQL)
#  - ẩn phiên bản PHP (header X-Powered-By)
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
 && echo "expose_php = Off" > "$PHP_INI_DIR/conf.d/zz-libgo.ini"

# Apache: ẩn phiên bản, tắt tự liệt kê file khi vào 1 thư mục
RUN sed -i 's/^ServerTokens .*/ServerTokens Prod/; s/^ServerSignature .*/ServerSignature Off/' /etc/apache2/conf-available/security.conf \
 && sed -i 's/Options Indexes FollowSymLinks/Options FollowSymLinks/' /etc/apache2/apache2.conf

# Cho Apache nghe theo biến PORT của Render (mặc định 80 khi chạy local)
ENV PORT=80
RUN sed -i 's/Listen 80/Listen ${PORT}/' /etc/apache2/ports.conf \
 && sed -i 's/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/' /etc/apache2/sites-available/000-default.conf

# Copy code vào web root
COPY . /var/www/html/

# Xóa những file KHÔNG được để ai tải về từ web
# (file SQL có email sinh viên, tài liệu, file cấu hình Docker...)
RUN rm -rf /var/www/html/sql /var/www/html/*.sql /var/www/html/*.md /var/www/html/*.bat \
           /var/www/html/Dockerfile /var/www/html/.dockerignore /var/www/html/.git /var/www/html/.gitignore

EXPOSE 80