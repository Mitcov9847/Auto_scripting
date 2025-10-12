# Лабораторная работа №3: Автоматизация запуска Python-скрипта с cron в Docker

## Цель работы

Освоить настройку планировщика задач **cron** для автоматического запуска Python-скрипта внутри контейнера Docker.

---

## Структура проекта

```text
lab03/
├─ logs/                         # Логи cron и ошибок
├─ cronjob                        # Расписание задач cron
├─ currency_exchange_rate.py      # Основной скрипт
├─ Dockerfile                     # Сборка контейнера (Python + cron)
├─ entrypoint.sh                  # Запуск cron при старте контейнера
├─ docker-compose.yml             # Управление контейнером через Docker Compose
├─ readme.md                      # Отчет по лабораторной работе
Объяснение:

logs/ — хранение логов cron.

cronjob — задания cron для автоматического вызова скрипта.

currency_exchange_rate.py — скрипт для получения курсов валют.

Dockerfile — описание сборки образа с Python и cron.

entrypoint.sh — настраивает cron и вывод логов.

docker-compose.yml — сборка и запуск контейнера.

Содержимое файлов
1️⃣ cronjob
cron
Копировать код
# Ежедневно в 06:00 — курс MDL→EUR за вчера
0 6 * * * python3 /app/currency_exchange_rate.py MDL EUR yesterday >> /var/log/cron.log 2>&1

# Еженедельно по пятницам в 17:00 — курс MDL→USD за прошлую неделю
0 17 * * 5 python3 /app/currency_exchange_rate.py MDL USD last_week >> /var/log/cron.log 2>&1
2️⃣ entrypoint.sh
sh
Копировать код
#!/bin/sh

echo "Creating log file..."
touch /var/log/cron.log
chmod 666 /var/log/cron.log

# Запуск cron и вывод логов
tail -f /var/log/cron.log &
exec cron -f
3️⃣ Dockerfile
dockerfile
Копировать код
FROM python:3.11-slim

RUN apt-get update && \
    apt-get install -y cron && \
    rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY currency_exchange_rate.py /app/
COPY cronjob /etc/cron.d/cronjob
COPY entrypoint.sh /entrypoint.sh

RUN chmod +x /entrypoint.sh \
    && chmod 0644 /etc/cron.d/cronjob \
    && ln -s /etc/cron.d/cron /etc/cron.d/cron

ENTRYPOINT ["/entrypoint.sh"]
4️⃣ docker-compose.yml
yaml
Копировать код
services:
  lab03_cron:
    build: .
    container_name: lab03_cron
    restart: always
Ход выполнения работы
Шаг 1. Сборка Docker-образа
powershell
Копировать код
docker compose build
Объяснение: Сборка контейнера с Python и cron, копирование файлов и настройка прав.

Скриншот сборки:

Шаг 2. Запуск контейнера
powershell
Копировать код
docker compose up -d --remove-orphans
Объяснение: Запуск контейнера в фоне и удаление старых "осиротевших" контейнеров.

Проверка работы контейнера:

powershell
Копировать код
docker ps
Скриншот:

Шаг 3. Проверка работы скрипта вручную
powershell
Копировать код
docker exec -it lab03_cron python3 /app/currency_exchange_rate.py MDL EUR 2025-10-11
Объяснение: Проверка корректного запуска скрипта внутри контейнера.

Пример вывода:

yaml
Копировать код
[2025-10-12 07:42:50.316087] currency_exchange_rate.py called with args: MDL EUR 2025-10-11
Скриншот:

Шаг 4. Просмотр логов cron
powershell
Копировать код
docker exec -it lab03_cron tail -f /var/log/cron.log
Объяснение: Отслеживание выполнения заданий cron в реальном времени.

Скриншот:

Шаг 5. Проверка cron-заданий
powershell
Копировать код
docker exec -it lab03_cron sh -lc "crontab -l"
Объяснение: Проверка, что cron загрузил задания из cronjob.

Скриншот:

Шаг 6. Проверка работы скрипта и сохранения результатов
powershell
Копировать код
docker exec -it lab03_cron python3 /app/currency_exchange_rate.py MDL EUR yesterday
docker exec -it lab03_cron cat /app/data/rate_MDL_EUR_<date>.json
Объяснение: Скрипт сохраняет результаты в JSON, которые можно использовать для анализа.

Пример вывода JSON:

json
Копировать код
{
  "from": "MDL",
  "to": "EUR",
  "rate": 0.05412,
  "date": "2025-10-11"
}
Скриншот:

Выводы
Контейнер с Python и cron создан и работает.

Скрипт currency_exchange_rate.py выполняется как вручную, так и по расписанию cron.

Логи cron и JSON-файлы подтверждают успешное выполнение заданий.

Все требования лабораторной работы №3 выполнены ✅

Библиография
Python Documentation — официальный справочник Python.

Docker Documentation — официальная документация Docker.

Docker Compose Documentation — руководство по Docker Compose.

Cron HowTo — Ubuntu Wiki — официальное руководство по cron.
