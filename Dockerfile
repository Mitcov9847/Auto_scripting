# Базовый образ Python
FROM python:3.11-slim

# Установка cron и необходимых зависимостей
RUN apt-get update && apt-get install -y cron && rm -rf /var/lib/apt/lists/*

# Копирование скрипта и cronjob
COPY currency_exchange_rate.py /app/
COPY cronjob /etc/cron.d/currency_cron
COPY entrypoint.sh /entrypoint.sh

# Установка прав для cron
RUN chmod 0644 /etc/cron.d/currency_cron
RUN crontab /etc/cron.d/currency_cron

# Создание рабочей директории
WORKDIR /app

# Стартовый скрипт
ENTRYPOINT ["/entrypoint.sh"]
