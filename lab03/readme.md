# Лабораторная работа №3: Автоматизация запуска Python-скрипта с cron в Docker

## Цель работы

Научиться настраивать планировщик задач **cron** внутри контейнера Docker для автоматического запуска Python-скрипта `currency_exchange_rate.py`.

## Подготовка

Работа основана на лабораторной работе №2.  

**Действия:**

1. Создать ветку `lab03` в проекте.  
2. Создать директорию `lab03` и скопировать туда файлы из `lab02`.

```bash
git checkout -b lab03
mkdir lab03
cp -r lab02/* lab03/
Скриншот структуры проекта:

---
## Задание
В каталоге lab03 создать файл cronjob с задачами cron:

```
Ежедневно в 06:00 — курс MDL→EUR за вчера
0 6 * * * python3 /app/currency_exchange_rate.py MDL EUR yesterday >> /var/log/cron.log 2>&1

Еженедельно в пятницу 17:00 — курс MDL→USD за прошлую неделю
0 17 * * 5 python3 /app/currency_exchange_rate.py MDL USD last_week >> /var/log/cron.log 2>&1
Пояснение:

0 6 * * * — ежедневное выполнение в 06:00.

0 17 * * 5 — еженедельное выполнение по пятницам в 17:00.

>> /var/log/cron.log 2>&1 — вывод всех сообщений и ошибок в файл логов.

Настройка entrypoint.sh
Для удобной работы с cron рекомендуется использовать скрипт entrypoint.sh, который настраивает и запускает cron при старте контейнера:

```
#!/bin/sh

create_log_file() {
    echo "Creating log file..."
    touch /var/log/cron.log
    chmod 666 /var/log/cron.log
    echo "Log file created at /var/log/cron.log"
}

monitor_logs() {
    echo "=== Monitoring cron logs ==="
    tail -f /var/log/cron.log
}

run_cron() {
    echo "=== Starting cron daemon ==="
    exec cron -f
}
```
# Экспорт переменных окружения для cron
```
env > /etc/environment
create_log_file
monitor_logs &
run_cron
```
Пояснение:

Создает лог-файл /var/log/cron.log.

Мониторит лог в реальном времени (tail -f).

Запускает cron как основной процесс контейнера.

Экспортирует переменные окружения для корректной работы скрипта.

Создание Dockerfile
```
FROM python:3.11-slim
```
# Установка зависимостей для cron
```
RUN apt-get update && \
    apt-get install -y cron && \
    rm -rf /var/lib/apt/lists/*

WORKDIR /app
```
# Копирование скрипта и файлов cron
```
COPY currency_exchange_rate.py /app/
COPY cronjob /etc/cron.d/cronjob
COPY entrypoint.sh /entrypoint.sh
```
# Назначение прав
```
RUN chmod +x /entrypoint.sh \
    && chmod 0644 /etc/cron.d/cronjob

ENTRYPOINT ["/entrypoint.sh"]
```
Пояснение:

Базовый образ — Python 3.11 slim.

Установка cron.

Копирование всех файлов проекта.

Назначение прав на скрипт и cron-задания.

ENTRYPOINT запускает cron автоматически при старте контейнера.

Создание docker-compose.yml
```
version: "3.9"

services:
  lab03_cron:
    build: .
    container_name: lab03_cron
    restart: always
    volumes:
      - ./data:/app/data
    env_file:
      - .env
```
Пояснение:

build: . — сборка контейнера из текущей директории.

container_name: lab03_cron — имя контейнера.

restart: always — автоматический перезапуск.

Монтирование папки data для хранения JSON с курсами валют.

Использование .env для переменных окружения (API_KEY, API_URL и др.).

## Ход выполнения
1. Сборка Docker-образа
```
docker compose build
```
Скриншот сборки:

2. Запуск контейнера
```
docker compose up -d --remove-orphans
```
Проверка состояния контейнера:

```
docker ps
```
Скриншот:

3. Проверка cron-заданий
```
docker exec -it lab03_cron sh -lc "crontab -l"
```
Скриншот:

4. Проверка логов cron
```
docker exec -it lab03_cron tail -f /var/log/cron.log
```
Скриншот:

5. Проверка работы скрипта вручную
```
docker exec -it lab03_cron python3 /app/currency_exchange_rate.py MDL EUR yesterday
```
Скриншот успешного запуска:

Проверка файла с результатами:

```
docker exec -it lab03_cron cat /app/data/rate_MDL_EUR_2025-10-11.json
```
Пример содержимого JSON:

```
{
  "from": "MDL",
  "to": "EUR",
  "rate": 0.05412,
  "date": "2025-10-11"
}
```
Скриншот JSON:

## Выводы
Контейнер с Python и cron успешно создан и запущен.

Скрипт currency_exchange_rate.py выполняется автоматически по расписанию cron.

Ручной запуск скрипта подтверждает корректную работу и сохранение результатов.

Логи cron подтверждают выполнение заданий и запись ошибок.

Все цели лабораторной работы №3 достигнуты ✅

## Библиография
Python Documentation

Docker Documentation

Docker Compose Documentation

Cron HowTo — Ubuntu Wiki


