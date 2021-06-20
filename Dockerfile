FROM hhvm/hhvm-proxygen:latest
RUN apt-get update -y && apt-get install -y curl && apt-get install -y php-curl && apt-get install -y php-mysql && apt-get install -y php-xml
RUN apt-get update -y && apt-get install -y php-mbstring --fix-missing
RUN add-apt-repository ppa:ondrej/php
RUN apt-get update -y && apt-get install -y php7.4-gd --fix-missing
VOLUME /var/www
