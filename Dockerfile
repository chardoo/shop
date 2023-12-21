# # Use an official PHP runtime as a parent image
# FROM php:7.4-apache

# # Set the working directory in the container
# #WORKDIR /var/www/html
# # Set working directory
# WORKDIR /var/www/html

# # Copy application code
# COPY . .

# # Install PHP extensions and other dependencies
# RUN apt-get update && \
#     apt-get install -y libpng-dev && \
#     docker-php-ext-install pdo pdo_mysql gd

# # Expose the port Apache listens on
# EXPOSE 80

# # Start Apache when the container runs
# CMD ["start-apache"]


FROM php:7.0-fpm

#WORKDIR /var/www/html

# # Copy application code
COPY . .
RUN docker-php-ext-install mysqli pdo pdo_mysql
RUN docker-php-ext-enable mysqli

EXPOSE 90

CMD ["start-apache"]