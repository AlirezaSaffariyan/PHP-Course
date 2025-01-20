# Use the official PHP image with Apache
FROM public.ecr.aws/docker/library/php:apache

# Install mysqli extension
RUN docker-php-ext-install mysqli
RUN apt update && apt install python3 python3-pip -y
RUN apt install python3-requests python3-bs4 -y