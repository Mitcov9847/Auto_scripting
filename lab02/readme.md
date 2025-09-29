# Лабораторная работа №2: Python-скрипт для работы с API

## Цель работы
Изучить взаимодействие Python-скрипта с Web API: отправка HTTP-запросов, обработка данных, сохранение в JSON и ведение логов ошибок.

## Задание

- Получение курса одной валюты к другой на заданную дату (аргументы командной строки: валюта и дата)
- Сохранение результата в JSON-файл с именем, содержащим валюты и дату
- Помещение файлов в папку `data` (создаётся автоматически, если нет)
- Обработка ошибок и запись их в `error.log`
- Поддержка вывода списка доступных валют
- Тестирование на нескольких датах с равными интервалами (минимум 5)

## Структура проекта

```
lab02/
├─ app/                       # код API-сервиса
├─ data/                      # JSON-файлы с курсами
├─ .env                       # ключ API
├─ currency_exchange_rate.py  # основной скрипт
├─ docker-compose.yaml        # запуск сервиса
├─ error.log                  # лог ошибок
├─ readme.md                  # этот файл
├─ requirements.txt           # зависимости Python
└─ .venv/                     # виртуальное окружение
```

## Подготовка среды

1. Установка Docker и Docker Compose
2. Копирование шаблона `.env`:

```powershell
cp sample.env .env
```

3. Запуск сервиса:

```powershell
docker-compose up -d --build
```

4. Проверка доступности API и списка валют:

```powershell
Invoke-WebRequest -Uri "http://localhost:8080/?currencies" -Method POST -Body "key=EXAMPLE_API_KEY"
```
<img width="987" height="601" alt="image" src="https://github.com/user-attachments/assets/8674c981-575d-47ae-94fb-5a603af47a73" />

**Пример ответа:**

```json
{"error":"","data":["MDL","USD","EUR","RON","RUS","UAH"]}
```

## Работа с Python

1. Создание виртуального окружения:

```powershell
python -m venv .venv
.\.venv\Scripts\activate
```

2. Установка зависимостей:

```powershell
pip install -r requirements.txt
```

3. Запуск скрипта на одной дате:

```powershell
python .\currency_exchange_rate.py USD MDL 2025-01-01 --url http://localhost:8080 --api-key EXAMPLE_API_KEY
```

**Результат:**
- Файл `data/rate_USD_MDL_2025-01-01.json` создан
- В консоли отображается курс валюты
- Ошибок нет

## Тестирование на диапазоне дат

```powershell
$dates = @('2025-01-01','2025-03-01','2025-05-01','2025-07-01','2025-09-01')
foreach($d in $dates){
  python .\currency_exchange_rate.py USD MDL $d --url http://localhost:8080 --api-key EXAMPLE_API_KEY
}
```

**Результат:**
- 5 JSON-файлов с курсами валют на соответствующие даты в папке `data`
- Ошибок нет

## Вывод списка валют через скрипт

```powershell
python .\currency_exchange_rate.py USD MDL 2025-01-01 --url http://localhost:8080 --api-key EXAMPLE_API_KEY --list-currencies
```

- Скрипт выводит поддерживаемые валюты

## Выводы

- Скрипт `currency_exchange_rate.py` успешно получает курсы валют, сохраняет данные в JSON и логирует ошибки
- Поддерживает вывод списка валют и корректно работает на нескольких датах
- Цель лабораторной работы достигнута: освоены навыки работы с API, аргументами командной строки, логированием и сохранением данных

