# Лабораторная работа: Создание резервной копии директории с помощью Shell скрипта

## Цель работы
Автоматизировать создание резервной копии важной директории с использованием Shell скрипта `backup.sh`.  
Скрипт должен:  
- Принимать два аргумента: исходную директорию и директорию для сохранения бэкапа (второй аргумент опциональный, по умолчанию `/backup`).  
- Создавать архив формата `.tar.gz` с текущей датой в имени.  
- Проверять существование директорий и выводить соответствующие сообщения об ошибках.  

---

## Ход выполнения работы

### Шаг 1. Создание структуры для тестирования

Создана рабочая директория `lab1` с поддиректориями:

```bash
mkdir -p lab1/source lab1/dest
cd lab1
В директорию source добавлены тестовые файлы:

bash
Копировать код
echo "Тестовый файл 1" > source/file1.txt
echo "Тестовый файл 2" > source/file2.txt
mkdir -p source/subdir
echo "Подфайл" > source/subdir/file3.txt
Структура папки source после добавления файлов:

bash
Копировать код
source/
├── file1.txt
├── file2.txt
└── subdir/
    └── file3.txt
<!-- Вставьте скриншот структуры папки здесь -->

Шаг 2. Создание скрипта backup.sh
Файл backup.sh содержит следующий код:

bash
Копировать код
#!/usr/bin/env bash
# backup.sh - Создание tar.gz резервной копии директории
# Usage: ./backup.sh <source_dir> [destination_dir]
# default destination_dir = /backup

set -euo pipefail

usage() {
  echo "Использование: $0 <source_dir> [destination_dir]"
  exit 1
}

SOURCE_DIR="${1:-}"
DEST_DIR="${2:-/backup}"

if [[ -z "$SOURCE_DIR" ]]; then
  echo "Ошибка: не указан путь к директории для бэкапа."
  usage
fi

if [[ ! -d "$SOURCE_DIR" ]]; then
  echo "Ошибка: директория '$SOURCE_DIR' не найдена."
  exit 2
fi

if [[ ! -d "$DEST_DIR" ]]; then
  echo "Директория назначения '$DEST_DIR' не существует. Создаю..."
  mkdir -p "$DEST_DIR" || { echo "Ошибка: не удалось создать '$DEST_DIR'."; exit 3; }
fi

BASENAME=$(basename "$SOURCE_DIR")
DATE="$(date +%Y-%m-%d_%H-%M-%S)"
ARCHIVE_NAME="${BASENAME}_backup_${DATE}.tar.gz"
ARCHIVE_PATH="${DEST_DIR%/}/$ARCHIVE_NAME"

TMP_ARCHIVE="${ARCHIVE_PATH}.part"

cleanup_on_exit() {
  local rc=$?
  [[ -f "$TMP_ARCHIVE" ]] && rm -f "$TMP_ARCHIVE"
  exit $rc
}
trap cleanup_on_exit EXIT

echo "Создаю бэкап '$SOURCE_DIR' -> '$ARCHIVE_PATH' ..."
tar -C "$(dirname "$SOURCE_DIR")" -czf "$TMP_ARCHIVE" "$(basename "$SOURCE_DIR")"

mv "$TMP_ARCHIVE" "$ARCHIVE_PATH"

echo "Бэкап успешно создан: $ARCHIVE_PATH"
ls -lh "$ARCHIVE_PATH"

trap - EXIT
exit 0
Шаг 3. Присвоение прав на выполнение
bash
Копировать код
chmod +x backup.sh
Шаг 4. Проверка работы скрипта
Создание бэкапа в папку dest:

bash
Копировать код
./backup.sh ./source ./dest
Вывод скрипта:

bash
Копировать код
Создаю бэкап './source' -> './dest/source_backup_2025-09-13_22-32-20.tar.gz' ...
Бэкап успешно создан: ./dest/source_backup_2025-09-13_22-32-20.tar.gz
-rw-r--r-- 1 mihai 197609 271 Sep 13 22:32 ./dest/source_backup_2025-09-13_22-32-20.tar.gz
<!-- Вставьте скриншот вывода здесь -->

Проверка содержимого архива:

bash
Копировать код
tar -tzf dest/source_backup_*.tar.gz
Вывод:

bash
Копировать код
source/
source/file1.txt
source/file2.txt
source/subdir/
source/subdir/file3.txt
<!-- Вставьте скриншот содержимого архива здесь -->

Проверка обработки ошибок:

Без аргументов:

bash
Копировать код
./backup.sh
Вывод:

makefile
Копировать код
Ошибка: не указан путь к директории для бэкапа.
Использование: ./backup.sh <source_dir> [destination_dir]
Несуществующая исходная директория:

bash
Копировать код
./backup.sh ./no_such_dir ./dest
Вывод:

bash
Копировать код
Ошибка: директория './no_such_dir' не найдена.
Вывод
Скрипт backup.sh успешно автоматизирует создание резервной копии директории:

Создаёт архив с текущей датой и временем.

Проверяет существование исходной и целевой директории.

Выводит информативные сообщения об ошибках.

Полностью соответствует требованиям лабораторной работы.
