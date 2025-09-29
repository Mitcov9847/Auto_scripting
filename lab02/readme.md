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

## Работа с Python

Запуск сервиса через Docker Compose
Перед написанием Python-скрипта необходимо создать рабочий файл настроек из шаблона, чтобы Docker знал, какой API-ключ использовать, а так же поднять локальный сервис, предоставляющий API.

Для этого в каталоге lab02 выполняются команды:

cp sample.env .env
и далее:

docker compose up -d

<img width="949" height="91" alt="image" src="https://github.com/user-attachments/assets/8f950978-6e14-44df-aaa9-7a3c33e75474" />

1. Создание виртуального окружения:

```powershell
python -m venv .venv
.\.venv\Scripts\activate
```

2. Установка зависимостей:

```powershell
pip install -r requirements.txt
```
<img width="970" height="566" alt="image" src="https://github.com/user-attachments/assets/a71fc343-565c-4179-b462-d4eda1322284" />

3. Запуск скрипта на одной дате:

```powershell
python .\currency_exchange_rate.py USD MDL 2025-01-01 --url http://localhost:8080 --api-key EXAMPLE_API_KEY
```
<img width="977" height="98" alt="image" src="https://github.com/user-attachments/assets/18fadfc6-52f1-4499-a21a-5b0be2f11a06" />

<img width="344" height="163" alt="image" src="https://github.com/user-attachments/assets/b792835e-b210-4a64-9e36-81128904fe6b" />

## Тестирование на диапазоне дат

```powershell
$dates = @('2025-01-01','2025-03-01','2025-05-01','2025-07-01','2025-09-01')
foreach($d in $dates){
  python .\currency_exchange_rate.py USD MDL $d --url http://localhost:8080 --api-key EXAMPLE_API_KEY
}
```

<img width="954" height="279" alt="image" src="https://github.com/user-attachments/assets/ae02a7e7-3248-42a1-93de-bcafd98e95e1" />


##  Запуск скрипта для диапазона дат

Для тестирования работы скрипта на нескольких датах с равными интервалами был выбран период с 2025-01-01 по 2025-09-01 с шагом в два месяца.

Использовался цикл в PowerShell:

$dates = @('2025-01-01','2025-03-01','2025-05-01','2025-07-01','2025-09-01')
foreach($d in $dates){
    python .\currency_exchange_rate.py USD MDL $d --url http://localhost:8080 --api-key EXAMPLE_API_KEY
}

<img width="815" height="186" alt="image" src="https://github.com/user-attachments/assets/3a18c74c-48dc-4f97-9a64-494fd986b934" />

<img width="593" height="241" alt="image" src="https://github.com/user-attachments/assets/a4115435-b352-4777-ab49-1f16c7de0515" />

## Вывод списка доступных валют через Python-скрипт

Скрипт поддерживает отдельный режим для получения списка всех доступных валют, что удобно для проверки перед запросом курсов.

["MDL","USD","EUR","RON","RUS","UAH"]

## Выводы

- Скрипт `currency_exchange_rate.py` успешно получает курсы валют, сохраняет данные в JSON и логирует ошибки
- Поддерживает вывод списка валют и корректно работает на нескольких датах
- Цель лабораторной работы достигнута: освоены навыки работы с API, аргументами командной строки, логированием и сохранением данных

## Библиография

1. Python Software Foundation. *Python 3 Documentation*. [https://docs.python.org/3/](https://docs.python.org/3/)  
2. Python Software Foundation. *pip Documentation*. [https://pip.pypa.io/en/stable/](https://pip.pypa.io/en/stable/)  
3. Docker Inc. *Docker Documentation*. [https://docs.docker.com/](https://docs.docker.com/)  
4. Docker Inc. *Docker Compose Documentation*. [https://docs.docker.com/compose/](https://docs.docker.com/compose/)  
5. Requests Library. *Python HTTP for Humans*. [https://docs.python-requests.org/](https://docs.python-requests.
