#!/usr/bin/env python3
# currency_exchange_rate.py — тестовый скрипт для проверки cron

import sys
from datetime import datetime

def main():
    now = datetime.now().isoformat()
    args = " ".join(sys.argv[1:])
    print(f"[{now}] currency_exchange_rate.py called with args: {args}")

if __name__ == "__main__":
    main()
