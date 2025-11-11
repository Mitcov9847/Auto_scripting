
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
      - ./jenkins_home:/var/jenkins_home
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
<img width="1465" height="941" alt="{D03B4FD0-BBEC-4004-B119-B02527BBF66D}" src="https://github.com/user-attachments/assets/9d42236f-a6fb-4d20-b8f9-37fddc0f2e7c" />

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
<img width="697" height="43" alt="{D3BD43ED-4752-4134-A184-637D73794514}" src="https://github.com/user-attachments/assets/044d5904-06f0-4c90-a584-228e71a7830d" />

---

## Шаг 4 — Создал `Dockerfile` для ssh-agent
Файл `Dockerfile`:
```
FROM jenkins/ssh-agent:latest

COPY jenkins_agent_ssh_key /home/jenkins/agent/jenkins_agent_ssh_key

RUN chown jenkins:jenkins /home/jenkins/agent/jenkins_agent_ssh_key \
    && chmod 600 /home/jenkins/agent/jenkins_agent_ssh_key

```
Комментарий: добавил PHP и composer для запуска тестов PHPUnit на агенте.
<img width="1533" height="462" alt="{4394229A-0F08-40DC-BFF4-E4EA8532DA0B}" src="https://github.com/user-attachments/assets/d0a9d64a-bde3-47c2-8464-16f4a39a85bb" />

---

## Шаг 5 — Создал `.env` с публичным ключом
Содержимое `.env`:
```
JENKINS_AGENT_SSH_PUBKEY="ssh-rsa AAAAB3NzaC1y... jenia@DESKTOP-KADC37L"
```
Пояснение: в Windows я установил переменную в сессии командой `set` перед запуском compose, либо сохранил `.env` в каталоге.
<img width="1010" height="242" alt="{1D341F9C-0887-4E55-9F3F-728DFD246885}" src="https://github.com/user-attachments/assets/acdeac72-8063-4a4c-a1b6-65b51d3a6e5e" />

---

## Шаг 6 — Запуск Docker Compose
Команда:
```
docker compose up -d --build
```
Ожидаемый вывод: оба контейнера `jenkins-controller` и `ssh-agent` в статусе `Up`. Я проверял через:
<img width="1194" height="670" alt="{4EBE735B-055A-419A-BA86-A9889D7FC28E}" src="https://github.com/user-attachments/assets/ba37a49b-5d47-48c2-8b9d-1e29c4969c93" />

```
docker ps
```
<img width="1199" height="207" alt="{DE1EE6FD-15B7-435B-9700-99483E3DA41C}" src="https://github.com/user-attachments/assets/bbe6fd4f-9b7a-43d9-96d2-2bfe52504819" />

---

## Шаг 7 — Первичная настройка Jenkins (в браузере)
1. Перешёл на `http://localhost:8080`.
<img width="1101" height="607" alt="{EC33160C-5139-4920-B99E-810A41AEDFF6}" src="https://github.com/user-attachments/assets/3589edee-2a04-46b1-811a-0682fba30a92" />
3. Получил пароль администратора:
```
docker exec -it jenkins-controller cat /var/jenkins_home/secrets/initialAdminPassword
```

3. Вставил пароль в поле Unlock Jenkins, выбрал **Install suggested plugins**, затем создал администратора.
<img width="1188" height="32" alt="{3F67982F-0D33-4FAC-82E6-118580AD4872}" src="https://github.com/user-attachments/assets/e69ada28-69c8-4908-b63f-707c59d94e2a" />

---

## Шаг 8 — Настройка учётных данных (Credentials) в Jenkins
1. В веб-интерфейсе: **Управление Jenkins → Учётные данные → System → Global**  
2. Нажал **Add Credentials** → **SSH Username with private key**  
   - Username: `jenkins`
   - Private Key: **Enter directly** — вставил содержимое `jenkins_agent_ssh_key`
   - Passphrase: пусто  
3. Создал запись.
4. 
<img width="1256" height="605" alt="{AE1DD000-47ED-4C9E-915E-A131C1290176}" src="https://github.com/user-attachments/assets/ed3a9440-22a8-4b74-bb1c-9800016d6eb0" />
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

<img width="1215" height="450" alt="{070C83FD-BC9D-4F28-AB2C-DEC1F30118E9}" src="https://github.com/user-attachments/assets/497f57ee-a1d1-47f9-861c-d7235699b0bc" />
<img width="1913" height="816" alt="{FFE95B3F-4D0E-440F-BAD5-F9112D0D9C42}" src="https://github.com/user-attachments/assets/370cebd4-b893-4f4c-abb2-0d0b72bdc8e7" />

---

## Шаг 10 — Отладка SSH-подключения и исправление проблем (кратко)

Во время настройки я столкнулся с несколькими проблемами — здесь пошагово как я их решал:

### Проблема A — `ssh-agent` не появлялся как контейнер
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

