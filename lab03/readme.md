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
```

## Структура проекта:
- **currency_exchange_rate.py** – скрипт для получения курса валют (MDL → EUR / USD).  
- **cronjob** – задания cron для автоматического запуска скрипта по расписанию.  
- **Dockerfile** – билд Docker-образа с установкой Python, cron и копированием скриптов.  
- **entrypoint.sh** – запускает cron при старте контейнера и создаёт лог файл.  
- **docker-compose.yml** – конфигурация для сборки и запуска контейнера с cron через Docker Compose.  
- **readme.md** – инструкция по сборке, запуску и проверке логов проекта.  
- **logs/** – директория для хранения логов cron (если используется).
---
## Задание

```
В каталоге lab03 создать файл cronjob с задачами cron:
Ежедневно в 06:00 — курс MDL→EUR за вчера
0 6 * * * python3 /app/currency_exchange_rate.py MDL EUR yesterday >> /var/log/cron.log 2>&1

Еженедельно в пятницу 17:00 — курс MDL→USD за прошлую неделю
0 17 * * 5 python3 /app/currency_exchange_rate.py MDL USD last_week >> /var/log/cron.log 2>&1
```

## Настройка entrypoint.sh
Для удобной работы с cron рекомендуется использовать скрипт entrypoint.sh, который настраивает и запускает cron при старте контейнера:

<img width="567" height="603" alt="image" src="https://github.com/user-attachments/assets/2a7910be-51d8-4290-90c0-b5b94a2fe878" />

## Экспорт переменных окружения для cron

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

## Создание Dockerfile
<img width="744" height="385" alt="image" src="https://github.com/user-attachments/assets/7c012dba-6920-4051-846a-585c941bb65f" />

## Установка зависимостей для cron
```
RUN apt-get update && \
    apt-get install -y cron && \
    rm -rf /var/lib/apt/lists/*

WORKDIR /app
```
## Копирование скрипта и файлов cron
```
COPY currency_exchange_rate.py /app/
COPY cronjob /etc/cron.d/cronjob
COPY entrypoint.sh /entrypoint.sh
```

## Создание docker-compose.yml
<img width="417" height="292" alt="image" src="https://github.com/user-attachments/assets/7b0d28d9-32f4-4b6d-9b8a-bc3dc004f187" />

Пояснение:
build: . — сборка контейнера из текущей директории.
container_name: lab03_cron — имя контейнера.
restart: always — автоматический перезапуск.
Монтирование папки data для хранения JSON с курсами валют.
Использование .env для переменных окружения (API_KEY, API_URL и др.).

# 4. Результаты запуска
## 4.1. Сборка Docker-образа
```
PS C:\Users\mihai\Desktop\AS\lab03> docker compose build
```

Вывод:
<img width="816" height="305" alt="image" src="https://github.com/user-attachments/assets/e28b919c-0664-41f7-b057-3e228c1e3f62" />

Комментарий:
Образ lab03-cronjob успешно собран. Все зависимости, включая cron, установлены.
<img width="679" height="136" alt="image" src="https://github.com/user-attachments/assets/0309a4b1-07d4-48f5-8ec4-ea05e49596aa" />

## 4.2. Запуск контейнера
```
PS C:\Users\mihai\Desktop\AS\lab03> docker compose up -d
```

Вывод:
<img width="817" height="162" alt="image" src="https://github.com/user-attachments/assets/930719c4-932d-4b36-87f2-0cc6f0c278d1" />

Проверка контейнеров:
<img width="815" height="157" alt="image" src="https://github.com/user-attachments/assets/f17ad701-8c9b-44dc-a9ae-102ba5c67d00" />

## 4.3. Проверка логов Cron
<img width="811" height="102" alt="image" src="https://github.com/user-attachments/assets/1b042162-d450-4efd-9f73-4b13f7d2aacd" />

Комментарий:
Каждую минуту cron запускает скрипт currency_exchange_rate.py, а результат записывается в cron.log.

## 4.4. Ручной запуск скрипта внутри контейнера
```
PS C:\Users\mihai\Desktop\AS\lab03> docker exec -it lab03_cron python3 /app/currency_exchange_rate.py MDL EUR 2025-10-11
```
Вывод:
[2025-10-12T07:42:50.316087] currency_exchange_rate.py called with args: MDL EUR 2025-10-11
Для дополнительного подтверждения корректности работы был выполнен ручной запуск Python-скрипта внутри контейнера.

Команда:
```
docker exec -it lab03-cron sh -lc "python3 /app/currency_exchange_rate.py --from MDL --to EUR --date yesterday"
```

Результат:
Saved: /app/data/rate_MDL_EUR_2025-10-10.json

Просмотр содержимого файла:
docker exec -it lab03-cron sh -lc "cat /app/data/rate_MDL_EUR_2025-10-10.json"

```
{
  "from": "MDL",
  "to": "EUR",
  "rate": 0.05412687933747653,
  "date": "2025-10-10"
}
```

Эта проверка подтверждает, что скрипт работает корректно и может выполняться как по расписанию через cron, так и вручную через консоль.

## 4.5. Проверка директории проекта и логов
```
PS C:\Users\mihai\Desktop\AS\lab03> dir
```
Вывод:

```
Mode                 LastWriteTime         Length Name
----                 -------------         ------ ----
d-----        12.10.2025     10:21                logs
-a----        12.10.2025     10:08            497 cronjob
-a----        12.10.2025     10:08            365 currency_exchange_rate.py
-a----        12.10.2025     10:10            170 docker-compose.yml
-a----        12.10.2025     10:09            807 Dockerfile
-a----        12.10.2025     10:20            437 entrypoint.sh
-a----        12.10.2025     10:10            519 readme.md
```
<img width="1239" height="597" alt="image" src="https://github.com/user-attachments/assets/315d5925-49d8-4d35-869f-4936c11d95e4" />


## Выводы
Контейнер с Python и cron успешно создан и запущен.
Скрипт currency_exchange_rate.py выполняется автоматически по расписанию cron.
Ручной запуск скрипта подтверждает корректную работу и сохранение результатов.
Логи cron подтверждают выполнение заданий и запись ошибок.

## Библиография
Python Documentation
Docker Documentation
Docker Compose Documentation
Cron HowTo — Ubuntu Wiki
