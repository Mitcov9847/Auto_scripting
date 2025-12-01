FROM jenkins/ssh-agent

RUN apt-get update \
 && apt-get install -y php-cli php-xml php-mbstring git curl unzip \
 && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
 && rm -rf /var/lib/apt/lists/*
