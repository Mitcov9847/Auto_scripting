
# Лабораторная работа: Создание резервной копии директории с помощью Shell скрипта

## Цель работы
Автоматизировать создание резервной копии важной директории с использованием Shell скрипта `backup.sh`.  
Скрипт должен:  
- Принимать два аргумента: исходную директорию и директорию для сохранения бэкапа (второй аргумент опциональный, по умолчанию `/backup`).  
- Создавать архив формата `.tar.gz` с текущей датой в имени.  
- Проверять существование директорий и выводить соответствующие сообщения об ошибках.  
## Теоретическая часть

### 1. Резервное копирование (backup)

Резервное копирование — это процесс создания копий данных для их сохранности и последующего восстановления в случае потери или повреждения исходной информации. Основные цели резервного копирования:

- Защита данных от случайного удаления или повреждения.
- Обеспечение возможности восстановления системы после сбоев.
- Хранение исторических версий файлов для анализа изменений.

Типы резервного копирования:

1. **Полное (Full backup)** – копируются все файлы и папки.
2. **Инкрементное (Incremental backup)** – копируются только изменения после последнего бэкапа.
3. **Дифференциальное (Differential backup)** – копируются изменения после последнего полного бэкапа.

---

### 2. Архивирование с помощью tar и gzip

В Linux/Unix-системах для резервного копирования часто используют утилиты `tar` и `gzip`:

- **tar** (Tape Archive) — объединяет несколько файлов и каталогов в один архив.  
  Пример команды:

```
  tar -czf backup.tar.gz /path/to/source
```
---

## Ход выполнения работы

### Шаг 1. Создание структуры для тестирования

Создана рабочая директория `lab1` с поддиректориями:

```
mkdir -p lab1/source lab1/dest
cd lab1
```

В директорию source добавлены тестовые файлы:

```
echo "Тестовый файл 1" > source/file1.txt
echo "Тестовый файл 2" > source/file2.txt
mkdir -p source/subdir
echo "Подфайл" > source/subdir/file3.txt
```
<img width="553" height="305" alt="image" src="https://github.com/user-attachments/assets/cad31ad2-209b-4865-a227-e6f402d4a07f" />

Структура папки source после добавления файлов:
```
source/
├── file1.txt
├── file2.txt
└── subdir/
    └── file3.txt
```
<img width="518" height="215" alt="image" src="https://github.com/user-attachments/assets/020087eb-2cef-43c8-b17e-f77225d6409c" />

### Шаг 2. Создание скрипта backup.sh
Файл backup.sh содержит следующий код:

```
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
```
<img width="727" height="471" alt="image" src="https://github.com/user-attachments/assets/08c36066-6c92-4793-92a5-36399c1fed27" />

### Шаг 3. Присвоение прав на выполнение

```
chmod +x backup.sh
```
Шаг 4. Проверка работы скрипта
Создание бэкапа в папку dest:

```
./backup.sh ./source ./dest
```
<img width="1201" height="207" alt="image" src="https://github.com/user-attachments/assets/9f4b1045-96fd-4956-bbba-80b6f7fc9251" />

### Вывод скрипта:

```
Создаю бэкап './source' -> './dest/source_backup_2025-09-13_22-32-20.tar.gz' ...
Бэкап успешно создан: ./dest/source_backup_2025-09-13_22-32-20.tar.gz
-rw-r--r-- 1 mihai 197609 271 Sep 13 22:32 ./dest/source_backup_2025-09-13_22-32-20.tar.gz
```

```
tar -tzf dest/source_backup_*.tar.gz
```
Вывод:

```
source/
source/file1.txt
source/file2.txt
source/subdir/
source/subdir/file3.txt
```
<img width="887" height="232" alt="image" src="https://github.com/user-attachments/assets/041b00c8-3323-4161-a067-150912d28f8e" />

Вывод
Скрипт backup.sh успешно автоматизирует создание резервной копии директории:

Создаёт архив с текущей датой и временем.
Проверяет существование исходной и целевой директории.
Выводит информативные сообщения об ошибках.
Полностью соответствует требованиям лабораторной работы.
