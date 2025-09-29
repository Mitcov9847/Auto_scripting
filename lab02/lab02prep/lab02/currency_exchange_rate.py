import os
import sys
import json
import requests
from datetime import datetime

# Конфигурация
API_URL = "http://localhost:8080/"
API_KEY = "EXAMPLE_API_KEY"  # Или читай из os.getenv("API_KEY")

# Создание папки data, если не существует
if not os.path.exists("data"):
    os.makedirs("data")

# Логирование ошибок
def log_error(message):
    print("Error:", message)
    with open("error.log", "a") as f:
        f.write(f"{datetime.now()}: {message}\n")

# Основная функция для получения курса
def get_exchange_rate(from_currency, to_currency, date):
    try:
        # Проверка формата даты
        datetime.strptime(date, "%Y-%m-%d")

        # Параметры запроса
        params = {"from": from_currency.upper(), "to": to_currency.upper(), "date": date}
        data = {"key": API_KEY}

        # Отправка POST-запроса
        response = requests.post(API_URL, params=params, data=data)
        response.raise_for_status()

        result = response.json()
        if result.get("error"):
            log_error(result["error"])
            return None

        # Сохранение в файл JSON
        filename = f"data/{from_currency}_{to_currency}_{date}.json"
        with open(filename, "w") as f:
            json.dump(result["data"], f, indent=4)

        print(f"Data saved to {filename}")
        return result["data"]

    except requests.RequestException as e:
        log_error(f"Request failed: {e}")
    except ValueError as e:
        log_error(f"Invalid date format: {e}")
    except Exception as e:
        log_error(f"Unexpected error: {e}")

# Парсинг аргументов командной строки
if __name__ == "__main__":
    if len(sys.argv) != 4:
        print("Usage: python currency_exchange_rate.py <from_currency> <to_currency> <date>")
        sys.exit(1)

    from_currency = sys.argv[1]
    to_currency = sys.argv[2]
    date = sys.argv[3]

    get_exchange_rate(from_currency, to_currency, date)
