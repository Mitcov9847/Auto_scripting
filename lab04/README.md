
# Lab04 — Настройка Jenkins (Controller + SSH Agent) —  отчёт

---

##  Описание
Отчёт описывает последовательные шаги, которые я выполнил при настройке Jenkins Controller в Docker, создании SSH Agent-контейнера и подключении агента к контроллеру по SSH. Ниже — подробное пошаговое руководство с командами, пояснениями и разделом с решёнными проблемами.

---

## Структура репозитория (файлы, которые должны быть в `lab04`)
```
README.md
docker-compose.yml
Dockerfile                # для ssh-agent
jenkins_agent_ssh_key     # приватный ключ (секрет) — НЕ коммитить в публичный репозиторий
jenkins_agent_ssh_key.pub # публичный ключ
.env                      # содержит JENKINS_AGENT_SSH_PUBKEY
secrets/                  # (опционально) место для ключей
```

---

# Выполнение 

## Шаг 1 — Создал рабочую директорию
Команды:
```
mkdir lab04
cd lab04
```
Комментарий: я работал в `C:\Users\jenia\Desktop\AS_Croitor\lab04`.

---

## Шаг 2 — `docker-compose.yml`
Файл `docker-compose.yml`:
```
version: "3.8"

services:
  jenkins-controller:
    image: jenkins/jenkins:lts
    container_name: jenkins-controller
    ports:
      - "8080:8080"
      - "50000:50000"
    volumes:
      - jenkins_home:/var/jenkins_home
      - ./jenkins_agent_ssh_key:/var/jenkins_home/jenkins_agent_ssh_key:ro
    networks:
      - jenkins-network

  ssh-agent:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: ssh-agent
    environment:
      - JENKINS_AGENT_SSH_PUBKEY=${JENKINS_AGENT_SSH_PUBKEY}
    volumes:
      - jenkins_agent_volume:/home/jenkins/agent
    depends_on:
      - jenkins-controller
    networks:
      - jenkins-network

volumes:
  jenkins_home:
  jenkins_agent_volume:

networks:
  jenkins-network:
    driver: bridge
```

Комментарий: я добавил монтирование `jenkins_agent_ssh_key` в контроллер, чтобы второй этапу проверять ключ (в ходе работ пришлось изменить подход — см. раздел troubleshooting).

---

## Шаг 3 — Сгенерировал SSH-ключи
Выполнил в PowerShell (в папке `lab04`):
```
mkdir secrets
cd secrets
ssh-keygen -f jenkins_agent_ssh_key -N ""
```
Результат:
- `jenkins_agent_ssh_key` — приватный
- `jenkins_agent_ssh_key.pub` — публичный

---

## Шаг 4 — Создал `Dockerfile` для ssh-agent
Файл `Dockerfile` (в корне `lab04`):
```dockerfile
FROM jenkins/ssh-agent

RUN apt-get update \
 && apt-get install -y php-cli php-xml php-mbstring git curl unzip \
 && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
 && rm -rf /var/lib/apt/lists/*
```
Комментарий: добавил PHP и composer для запуска тестов PHPUnit на агенте.

---

## Шаг 5 — Создал `.env` с публичным ключом
Содержимое `.env`:
```
JENKINS_AGENT_SSH_PUBKEY="ssh-rsa AAAAB3NzaC1y... jenia@DESKTOP-KADC37L"
```
Пояснение: в Windows я установил переменную в сессии командой `set` перед запуском compose, либо сохранил `.env` в каталоге.

---

## Шаг 6 — Запуск Docker Compose
Команда:
```powershell
docker compose up -d --build
```
Ожидаемый вывод: оба контейнера `jenkins-controller` и `ssh-agent` в статусе `Up`. Я проверял через:
```powershell
docker ps
```

---

## Шаг 7 — Первичная настройка Jenkins (в браузере)
1. Перешёл на `http://localhost:8080`.  
2. Получил пароль администратора:
```powershell
docker exec -it jenkins-controller cat /var/jenkins_home/secrets/initialAdminPassword
```
3. Вставил пароль в поле Unlock Jenkins, выбрал **Install suggested plugins**, затем создал администратора.

---

## Шаг 8 — Настройка учётных данных (Credentials) в Jenkins
1. В веб-интерфейсе: **Управление Jenkins → Учётные данные → System → Global**  
2. Нажал **Add Credentials** → **SSH Username with private key**  
   - Username: `jenkins`
   - Private Key: **Enter directly** — вставил содержимое `jenkins_agent_ssh_key`
   - Passphrase: пусто  
3. Создал запись.

Комментарий: этот шаг необходим для того, чтобы Jenkins мог использовать приватный ключ при подключении к ssh-agent.

---

## Шаг 9 — Создание узла (Node / Agent) в Jenkins
1. **Manage Jenkins → Manage Nodes → New Node**  
2. Имя: `ssh-agent1`  
3. Тип: Permanent Agent  
4. Настройки:
   - Remote root directory: `/home/jenkins/agent`
   - Labels: `php-agent`
   - Executors: `1`
   - Launch method: `Launch agents via SSH`
   - Host: `ssh-agent`
   - Credentials: выбран созданный ключ
5. Сохранил → попытка подключения

---

## Шаг 10 — Отладка SSH-подключения и исправление проблем (кратко)

Во время настройки я столкнулся с несколькими проблемами — здесь пошагово как я их решал:

### Проблема A — `ssh-agent` не появлялся как контейнер
**Диагностика:** `docker ps` показывал только `jenkins-controller`.  
**Решение:** исправил `docker-compose.yml` (отступы и секцию `ssh-agent`), затем `docker compose up -d --build`.

---

### Проблема B — переменная `JENKINS_AGENT_SSH_PUBKEY` не применялась (Windows)
**Диагностика:** warning `The "JENKINS_AGENT_SSH_PUBKEY" variable is not set.`  
**Решение:** в PowerShell выполнил:
```powershell
set JENKINS_AGENT_SSH_PUBKEY="ssh-rsa AAAA... jenia@DESKTOP-KADC37L"
docker compose up -d --build
```
или сохранил строку в `.env`.

---

### Проблема C — `Permission denied (publickey)` при ssh
**Симптомы:** при попытке `ssh jenkins@ssh-agent` получал `Permission denied (publickey)`.  
**Диагностика:**
- проверил наличие ключа в Jenkins-контейнере:
  ```bash
  docker exec -it jenkins-controller bash
  ls -la /var/jenkins_home/jenkins_agent_ssh_key
  ```
- файл был смонтирован с правами `root` и открытыми разрешениями; `chown`/`chmod` внутри контейнера не работали (Read-only filesystem) из‑за особенностей volume.

**Решение:**
- Я изменил подход: при сборке образа ssh-agent я копировал публичный ключ в `authorized_keys` внутри образа (через `Dockerfile`) либо использовал .env чтобы контейнер сам создавал `authorized_keys`.  
- На хосте выставил права на файл командой PowerShell:
  ```powershell
  icacls jenkins_agent_ssh_key /grant:r "${env:USERNAME}:R"
  ```
- Удалял старую запись host key в `known_hosts` внутри Jenkins-контейнера:
  ```bash
  ssh-keygen -f "/var/jenkins_home/.ssh/known_hosts" -R "ssh-agent"
  ```
- После этого повторно пересобирал контейнеры `docker compose up -d --build`.

---

## Шаг 11 — Проверка работы агента
1. В Jenkins нажал **Launch agent** на странице узла.  
2. После успешного подключения статус изменился на **online**.  
3. Создал тестовую задачу `test-agent-job`, ограничил выполнение меткой `php-agent` и добавил команду:
```bash
echo "Hello from agent"
hostname
```
4. Запустил — в логах увидел ожидаемые строки и статус `SUCCESS`.

---

## Шаг 12 — Создание Jenkins Pipeline для PHP проекта (коротко)
В репозитории `https://github.com/iurii1801/auto_scripting` в ветке `lab04` находится `lab04/recipe-book`.  
Добавил `Jenkinsfile`:

```groovy
pipeline {
  agent { label 'php-agent' }

  stages {
    stage('Install Dependencies') {
      steps { dir('lab04/recipe-book') { sh 'composer install' } }
    }
    stage('Test') {
      steps { dir('lab04/recipe-book') { sh './vendor/bin/phpunit --testdox tests' } }
    }
  }
}
```

Настроил Job в Jenkins: New Item → Pipeline → Pipeline script from SCM → указал репозиторий, ветку `lab04` и `Script Path` = `lab04/recipe-book/Jenkinsfile`.

---

## Полезные команды (итоговый набор)

```powershell
# Запуск контейнеров
docker compose up -d --build

# Посмотреть работающие контейнеры
docker ps

# Получить пароль initialAdminPassword
docker exec -it jenkins-controller cat /var/jenkins_home/secrets/initialAdminPassword

# Войти в контейнер
docker exec -it jenkins-controller bash

# Удаление старого known_hosts записи внутри контейнера
ssh-keygen -f "/var/jenkins_home/.ssh/known_hosts" -R "ssh-agent"

# Проверка SSH вручную
ssh -i /var/jenkins_home/jenkins_agent_ssh_key -o StrictHostKeyChecking=no jenkins@ssh-agent
```

---

## Заключение и рекомендации

- Всю чувствительную информацию (приватные ключи) хранить вне публичного репозитория. Лучше использовать Jenkins Credentials или секретные хранилища.
- При работе на Windows объёмную часть прав удобнее регулировать через `icacls`.
- Для production-окружений рекомендую использовать динамические агенты (Docker Cloud / Kubernetes), а не статические SSH-агенты.
- Если агент не подключается — первым делом проверяйте логи контейнера (`docker logs <name>`) и `known_hosts`/permissions на ключи.

---

## Места под скриншоты

Вставьте сюда ссылки или изображения:

- Скриншот: вывод `docker ps`  
- Скриншот: Unlock Jenkins  
- Скриншот: Добавление Credentials в Jenkins  
- Скриншот: Конфигурация узла (Node)  
- Скриншот: Успешная сборка

---

### Готовый файл
Файл `README.md` сохранён и доступен для скачивания ниже.

