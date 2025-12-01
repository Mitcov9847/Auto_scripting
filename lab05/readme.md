# Лабораторная работа №5: Ansible playbook для конфигурации сервера

## Цель

Научиться создавать Ansible playbook для автоматизации конфигурации серверов.

---

## Ход выполнения работы

### Шаг 0. Подготовка

Для выполнения лабораторной работы необходимо создать папку `lab05`.  
Из предыдущей лабораторной работы №4 нужно скопировать следующие файлы:

- PHP-проект `recipe-book` с модульными тестами;
- файл `composer.json`;
- файл `Jenkinsfile`;
- папка `secrets` с SSH-ключами для Jenkins SSH-агента;
- `docker-compose.yml` и `Dockerfile` из IW04.

Эти файлы будут использованы как основа для настройки новых конвейеров Jenkins и создания Ansible playbook.

### Шаг 1. Установка и настройка Jenkins Controller

Jenkins будет использоваться для управления всеми этапами автоматизации в рамках лабораторной работы.

#### 1.1. Создание файла docker-compose.yaml и сервиса `jenkins-controller`

В файле необходимо описать сервис **`jenkins-controller`** на основе образа `jenkins/jenkins:lts`, с пробросом портов и volume для данных Jenkins.

```yaml
services:
  jenkins-controller:
    image: jenkins/jenkins:lts
    container_name: jenkins-controller
    ports:
      - "9090:8080"
      - "50000:50000"
    volumes:
      - jenkins_home:/var/jenkins_home
    restart: unless-stopped

volumes:
  jenkins_home:
```


#### 1.2. Запуск контейнера Jenkins Controller

Необходимо перейти в каталог `lab05` и запустить контейнер Jenkins с помощью Docker Compose:

```bash
cd lab05
docker compose up -d
```

#### 1.3. Первичная настройка Jenkins (Unlock Jenkins)

Необходимо открыть Jenkins в браузере по адресу:

```text
http://localhost:9090
```

При первом запуске Jenkins запрашивает пароль администратора.
Пароль берётся из логов контейнера:

```bash
docker logs jenkins-controller
```

Нужно скопировать значение **initialAdminPassword**, вставить его в поле **Administrator password** и нажать **Continue**.

![image](https://i.imgur.com/dscRVkS.png)

#### 1.4. Установка необходимых плагинов (`Docker`, `Docker Pipeline`, `GitHub`, `SSH Agent`)

После разблокировки Jenkins необходимо пройти мастер настройки и установить плагины.

1. Выбрать вариант настройки плагинов (**Select plugins to install**).

![image](https://i.imgur.com/P7goGv9.png)
![image](https://i.imgur.com/LDZ6Buq.png)

2. После завершения мастера перейти в:

   **Manage Jenkins → Plugins → Available plugins**

3. Найти и установить следующие плагины:

   - **Docker plugin**
   - **Docker Commons Plugin**
   - **Docker Pipeline**
   - **Docker API Plugin**
   - **SSH Agent Plugin**
   - **SSH Build Agents plugin** (при наличии)
   - **GitHub Integration Plugin**
   - **GitHub Branch Source Plugin**

После установки плагинов убедиться, что они отображаются во вкладке **Installed plugins** и помечены как активные.

<img width="1543" height="382" alt="{B8B97083-57EB-462B-B816-C50A6C9D5CA5}" src="https://github.com/user-attachments/assets/a1e8fd0e-146a-460c-bd03-9dbb7428654c" />
<img width="1569" height="262" alt="{A32EE38E-3791-473F-A07D-BFD548FA5C60}" src="https://github.com/user-attachments/assets/2b8b2d0d-eca8-4b9d-af64-97b0831c54f5" />
<img width="1537" height="256" alt="{D0932DE0-03BA-498F-86AA-B572E7D613D4}" src="https://github.com/user-attachments/assets/b0d461df-7bf5-4e7c-bd4a-486a464eb39d" />
![image](https://i.imgur.com/1mIdwgy.png)

### Шаг 2. Подготовка SSH-агента

Агент SSH будет использоваться Jenkins для сборки PHP-проекта и запуска модульных тестов по SSH.

#### 2.1 Создание файла `Dockerfile.ssh_agent`

Нужно создать в папке `lab05` файл **`Dockerfile.ssh_agent`** и описать в нём образ SSH-агента.

```dockerfile
# SSH-агент для сборки PHP-проекта
FROM php:8.3-cli

# Устанавливаем SSH-сервер и нужные утилиты
RUN apt-get update && apt-get install -y --no-install-recommends \
    openssh-server \
    git \
    unzip \
    zip \
    rsync \
 && rm -rf /var/lib/apt/lists/*

# Устанавливаем Composer (для установки зависимостей PHP-проекта)
RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin --filename=composer

# Создаем пользователя, от имени которого Jenkins будет подключаться
RUN useradd -m -d /home/jenkins -s /bin/bash jenkins

# Готовим SSH
RUN mkdir -p /var/run/sshd

# Копируем публичный ключ в контейнер (ключи уже лежат в папке secrets из lab04)
# jenkins_agent_ssh_key.pub — ПУБЛИЧНЫЙ ключ
COPY secrets/jenkins_agent_ssh_key.pub /home/jenkins/.ssh/authorized_keys

RUN chown -R jenkins:jenkins /home/jenkins/.ssh && \
    chmod 700 /home/jenkins/.ssh && \
    chmod 600 /home/jenkins/.ssh/authorized_keys

EXPOSE 22

CMD ["/usr/sbin/sshd", "-D", "-e"]
```

Этот образ:

- основан на `php:8.3-cli`, чтобы был доступен PHP-CLI;
- устанавливает SSH-сервер и полезные утилиты;
- ставит Composer;
- создаёт пользователя `jenkins`, от имени которого Jenkins будет подключаться по SSH;
- копирует **публичный ключ** из папки `secrets` в `authorized_keys`;
- настраивает права на `.ssh` и запускает `sshd`.

![image](https://i.imgur.com/4ek6I0k.png)

#### 2.2 Подготовка SSH-ключей (повторение из lab04)

Ключи уже перенесены из лабораторной работы №4 в папку `secrets`, но по заданию нужно описать процесс их создания.

Для генерации пары ключей (один раз в lab04) используется команда:

```bash
C:\Users\jenia\Desktop\AS_Croitor\lab05\ssh_keys>ssh-keygen -t rsa -b 4096 -C "ansible" -f id_rsa -N ""
```
<img width="1119" height="381" alt="{D326B9AA-66E4-4D97-B94A-581E2FBD1532}" src="https://github.com/user-attachments/assets/921c4b95-09bc-4001-b44f-a47f4ea6c9ff" />

- `secrets/jenkins_agent_ssh_key` — **приватный** ключ (остаётся на хосте и в Jenkins Credentials).
- `secrets/jenkins_agent_ssh_key.pub` — **публичный** ключ (копируется в контейнер SSH-агента, как указано в Dockerfile).

#### 2.3 Добавление сервиса `ssh-agent` в docker-compose

Теперь нужно добавить сервис **`ssh-agent`** в файл `docker-compose.yaml`.

Файл должен содержать два сервиса: `jenkins-controller` и `ssh-agent`:

```yaml
services:
  jenkins-controller:
    image: jenkins/jenkins:lts
    container_name: jenkins-controller
    ports:
      - "9090:8080"
      - "50000:50000"
    volumes:
      - jenkins_home:/var/jenkins_home
    restart: unless-stopped

  ssh-agent:
    build:
      context: .
      dockerfile: Dockerfile.ssh_agent
    container_name: ssh-agent
    ports:
      - "2222:22"
    restart: unless-stopped

volumes:
  jenkins_home:
```

> Важно: порт `2222` на хосте проброшен на порт `22` внутри контейнера SSH-агента. Именно на `localhost:2222` потом будет подключаться Jenkins.

![image](https://i.imgur.com/ccLXwFe.png)

#### 2.4 Сборка образа SSH-агента и запуск контейнеров

Далее нужно:

1. Собрать образ **`ssh-agent`** по новому Dockerfile:

```bash
docker compose build ssh-agent
```

2. Запустить оба сервиса в фоне:

```bash
docker compose up -d
```

<img width="1142" height="259" alt="{4786E231-E45D-4971-B6AD-95CBE91B0C1E}" src="https://github.com/user-attachments/assets/3834e2a5-4a56-4c7e-b023-3ecfd083bb9f" />

3. Убедиться, что контейнеры запущены и порты проброшены корректно:

```bash
docker ps
```

Ожидаемый результат:

- контейнер **jenkins-controller** в статусе `Up`, порты `9090` и `50000` проброшены;
- контейнер **ssh-agent** в статусе `Up`, порт `2222->22/tcp`.

<img width="1150" height="268" alt="{413EAD0C-4714-45E8-A194-9FB1B7564665}" src="https://github.com/user-attachments/assets/d92dd8e5-978d-417b-952e-6519eec4708e" />


### Шаг 3. Создание агента Ansible

Агент Ansible будет использоваться Jenkins для выполнения Ansible playbook, который будет настраивать тестовый сервер.
На этом шаге необходимо создать Docker-образ с Ansible, подготовить SSH-ключи для Jenkins и для подключения к тестовому серверу, а также описать сервис `ansible-agent` в docker-compose.

#### 3.1. Создание файла **`Dockerfile.ansible_agent`**

Необходимо создать файл **`Dockerfile.ansible_agent`** в папке `lab05` и описать в нём образ Ansible-агента на базе Ubuntu 22.04.

Код файла:

```dockerfile
# Ansible-агент на базе Ubuntu
FROM ubuntu:22.04

ENV DEBIAN_FRONTEND=noninteractive

# Устанавливаем SSH-сервер, Python, Ansible и Java
RUN apt-get update && apt-get install -y --no-install-recommends \
    openssh-server \
    python3 \
    python3-pip \
    ansible \
    git \
    openjdk-21-jre-headless \
    && rm -rf /var/lib/apt/lists/*

# Создаём пользователя ansible
RUN useradd -m -d /home/ansible -s /bin/bash ansible && \
    mkdir -p /home/ansible/.ssh && \
    chown -R ansible:ansible /home/ansible/.ssh

# Готовим SSH-сервер
RUN mkdir -p /var/run/sshd

# Публичный ключ Jenkins -> Ansible-агент
# jenkins_ansible_ssh_key.pub будет сгенерирован в разделе 3.2
COPY secrets/jenkins_ansible_ssh_key.pub /home/ansible/.ssh/authorized_keys

RUN chown ansible:ansible /home/ansible/.ssh/authorized_keys && \
    chmod 700 /home/ansible/.ssh && \
    chmod 600 /home/ansible/.ssh/authorized_keys

# Ключи Ansible-агента для подключения к тестовому серверу (раздел 3.3)
COPY secrets/ansible_test_ssh_key /home/ansible/.ssh/id_ed25519
COPY secrets/ansible_test_ssh_key.pub /home/ansible/.ssh/id_ed25519.pub

RUN chown ansible:ansible /home/ansible/.ssh/id_ed25519* && \
    chmod 600 /home/ansible/.ssh/id_ed25519

# Папка для будущих настроек Ansible (inventory, ansible.cfg)
RUN mkdir -p /etc/ansible && chown -R ansible:ansible /etc/ansible

EXPOSE 22

CMD ["/usr/sbin/sshd", "-D", "-e"]
```

![image](https://i.imgur.com/QJFH16v.png)

#### 3.2. Генерация SSH-ключей для Jenkins → Ansible-агент

Эти ключи нужны Jenkins, чтобы подключаться к контейнеру ansible-agent через SSH.

Команда:

```bash
ssh-keygen -t ed25519 -f secrets/jenkins_ansible_ssh_key -C "jenkins->ansible-agent"
```
<img width="1108" height="416" alt="{4C99A36A-1A10-4CE4-8278-847E0D44F673}" src="https://github.com/user-attachments/assets/f52957b1-521d-41f6-b49f-57fd0d79642c" />

#### 3.3. Генерация SSH-ключей Ansible-агента → тестовый сервер

Эти ключи будут использоваться внутри контейнера ansible-agent для подключения к будущему тестовому серверу.

Команда:

```bash
jenkins@71886bb847d9:/$ ssh-keygen -t rsa -b 4096 -C "jenkins" -f /var/jenkins_home/.ssh/id_rsa -N ""
```
<img width="1084" height="383" alt="{5BB9756F-0DE6-49EC-8EF4-F4EFCFF45115}" src="https://github.com/user-attachments/assets/5a97b482-ce8c-4ad4-8bdc-68cdecba99d5" />

#### 3.4. Добавление сервиса `ansible-agent` в docker-compose

Необходимо добавить новый сервис в файл **docker-compose.yaml**:

```yaml
ansible-agent:
  build:
    context: .
    dockerfile: Dockerfile.ansible_agent
  container_name: ansible-agent
  ports:
    - "2223:22"
  restart: unless-stopped
```

![image](https://i.imgur.com/uDgClXF.png)

#### 3.5. Сборка Ansible-агента

Выполнить:

```bash
docker compose build ansible-agent
```
<img width="1187" height="539" alt="{F9B5C39F-AE52-4A76-BBE0-BCDA1A36873A}" src="https://github.com/user-attachments/assets/cc4402f9-5393-4088-881e-07220796fa3e" />

#### 3.6. Запуск контейнеров и проверка контейнеров

Запускаем все сервисы:

```bash
docker compose up -d
```

проверка контейнеров:

```bash
docker ps
```

Ожидается:

- `jenkins-controller` — running
- `ssh-agent` — running
- `ansible-agent` — running, порт `2223->22`

<img width="1149" height="236" alt="{0A756ABB-5922-49B6-9145-C867595015EB}" src="https://github.com/user-attachments/assets/6e75b794-c62c-4fcb-8659-22a1460c36ad" />


### Шаг 4. Создание тестового сервера

Тестовый сервер — это отдельный контейнер, на котором будет выполняться Ansible-playbook.
Задача тестового сервера — предоставить окружение, в котором:

- Ansible сможет подключаться по SSH от имени пользователя **ansible**,
- выполнять привилегированные команды (через sudo),
- и производить тестовые изменения.

На этом шаге создаётся Docker-образ тестового сервера, добавляется пользователь, настраивается SSH и пробрасывается порт.

#### 4.1 Создание файла **`Dockerfile.test_server`**

В каталоге `lab05` нужно создать файл **`Dockerfile.test_server`**.

Внутри него описывается тестовый сервер на базе Ubuntu 22.04.

```dockerfile
# Тестовый сервер на базе Ubuntu
FROM ubuntu:22.04

ENV DEBIAN_FRONTEND=noninteractive

# Ставим SSH-сервер и sudo (чтобы Ansible мог выполнять привилегированные задачи)
RUN apt-get update && apt-get install -y --no-install-recommends \
    openssh-server \
    sudo \
 && rm -rf /var/lib/apt/lists/*

# Создаём пользователя ansible
RUN useradd -m -d /home/ansible -s /bin/bash ansible && \
    mkdir -p /home/ansible/.ssh && \
    chown -R ansible:ansible /home/ansible/.ssh && \
    echo "ansible ALL=(ALL) NOPASSWD:ALL" > /etc/sudoers.d/ansible

# Готовим SSH-сервер
RUN mkdir -p /var/run/sshd

# Подключаем публичный ключ Ansible-агента
COPY secrets/ansible_test_ssh_key.pub /home/ansible/.ssh/authorized_keys

RUN chown ansible:ansible /home/ansible/.ssh/authorized_keys && \
    chmod 700 /home/ansible/.ssh && \
    chmod 600 /home/ansible/.ssh/authorized_keys

EXPOSE 22

PS C:\Users\jenia\Desktop\AS_Croitor\lab05> CMD ["tail", "-f", "/dev/null"]
```
<img width="783" height="79" alt="{7336CF08-CBB3-4925-A622-2C8B53DCF9A6}" src="https://github.com/user-attachments/assets/4221ecf7-d631-41be-bd9d-83c5e00b517c" />

#### 4.2 Добавление сервиса `test-server` в docker-compose.yaml

В файл `docker-compose.yaml` добавляется новый сервис:

```yaml
version: '3.8'

services:
  jenkins-controller:
    image: jenkins/jenkins:lts
    container_name: jenkins-controller
    ports:
      - "8080:8080"
      - "50000:50000"
    volumes:
      - jenkins_home:/var/jenkins_home
      - /var/run/docker.sock:/var/run/docker.sock
    restart: unless-stopped

  ssh-agent:
    build:
      context: .
      dockerfile: Dockerfile.ssh_agent
    container_name: ssh-agent
    volumes:
      - ./ssh_keys:/root/.ssh
    restart: unless-stopped

  ansible-agent:
    build:
      context: .
      dockerfile: Dockerfile.ansible_agent
    container_name: ansible-agent
    user: ansible
    volumes:
      - ./ssh_keys/id_rsa:/home/ansible/.ssh/id_rsa:ro
      - ./ssh_keys/id_rsa.pub:/home/ansible/.ssh/id_rsa.pub:ro
    restart: unless-stopped

  test-server:
    build:
      context: .
      dockerfile: Dockerfile.test_server
    container_name: test-server
    user: root
    ports:
      - "2222:22"
    volumes:
      - ./ssh_keys/id_rsa.pub:/home/ansible/.ssh/authorized_keys:ro
    restart: unless-stopped

volumes:
  jenkins_home:

```
<img width="1466" height="898" alt="{18F6112F-AD37-47DB-837A-5A323E5C3256}" src="https://github.com/user-attachments/assets/a00ddab4-7228-4bd2-88ce-266261fe9030" />

#### 4.3 Сборка образа тестового сервера

Выполняется сборка:

```bash
docker compose build test-server
```

<img width="585" height="54" alt="{FD1AB285-9630-4783-8E30-E36265C7DF7C}" src="https://github.com/user-attachments/assets/bf864a33-d961-4bb4-a3d5-5c0e62444074" />


#### 4.4 Запуск всех контейнеров

```bash
docker compose up -d
```
<img width="1128" height="258" alt="{EA10A31E-E37C-4EF8-8109-E63916066866}" src="https://github.com/user-attachments/assets/62fe4c24-7921-4d07-a668-cabb73d20dbf" />

### Шаг 5. Создание Ansible playbook для настройки тестового сервера

Далее нужно создать инфраструктура Ansible для автоматической настройки тестового сервера:

- в папке `ansible` необходимо определить инвентарный файл `hosts.ini` с параметрами подключения;
- Необходимо создать playbook `setup_test_server.yml`, который:

  - установит и настроит **Apache2**;
  - установит **PHP** и необходимые расширения;
  - настроит виртуальный хост Apache для PHP-проекта `recipe-book`.

#### 5.1. Создание папки `ansible` и файла `hosts.ini`

В каталоге `lab05` необходимо создать папку `ansible`.

Внутри папки `ansible` необходимо создать файл **`hosts.ini`** со следующим содержимым:

```ini
[test_server]
test-server ansible_host=test-server ansible_port=22 ansible_user=ansible ansible_ssh_private_key_file=/home/ansible/.ssh/id_ed25519 ansible_python_interpreter=/usr/bin/python3
```

Здесь:

- `test_server` — группа хостов Ansible (используется в playbook);
- `test-server` — имя контейнера Docker (из `docker-compose.yaml`);
- `ansible_user=ansible` — пользователь, под которым Ansible подключается по SSH;
- `ansible_ssh_private_key_file=/home/ansible/.ssh/id_ed25519` — путь к приватному ключу, который мы положили в образ `ansible-agent`;
- `ansible_python_interpreter=/usr/bin/python3` — путь к Python 3 на целевой машине.

#### 5.2. Создание Ansible playbook `setup_test_server.yml`

В той же папке `ansible` необходимо создать файл **`setup_test_server.yml`**, который выполняет все шаги по настройке тестового сервера:

```yaml
---
- name: Setup test server for PHP application
  hosts: test_server
  become: yes
  become_user: root

  vars:
    app_root: /var/www/recipe-book
    app_public: /var/www/recipe-book/public
    server_name: recipe-book.local

  tasks:
    - name: Update APT cache
      ansible.builtin.apt:
        update_cache: yes
        cache_valid_time: 3600

    - name: Install Apache2
      ansible.builtin.apt:
        name: apache2
        state: present

    - name: Install PHP and required extensions
      ansible.builtin.apt:
        name:
          - php
          - libapache2-mod-php
          - php-cli
          - php-mbstring
          - php-xml
          - php-mysql
        state: present

    - name: Enable Apache rewrite module
      ansible.builtin.command: a2enmod rewrite
      args:
        warn: false
      notify: Reload Apache

    - name: Create application root directory
      ansible.builtin.file:
        path: "{{ app_root }}"
        state: directory
        owner: www-data
        group: www-data
        mode: "0755"

    - name: Ensure public directory exists
      ansible.builtin.file:
        path: "{{ app_public }}"
        state: directory
        owner: www-data
        group: www-data
        mode: "0755"

    - name: Configure Apache virtual host for recipe-book
      ansible.builtin.copy:
        dest: /etc/apache2/sites-available/recipe-book.conf
        content: |
          <VirtualHost *:80>
              ServerName {{ server_name }}
              DocumentRoot {{ app_public }}

              <Directory {{ app_public }}>
                  AllowOverride All
                  Require all granted
              </Directory>

              ErrorLog ${APACHE_LOG_DIR}/recipe-book_error.log
              CustomLog ${APACHE_LOG_DIR}/recipe-book_access.log combined
          </VirtualHost>
      notify: Reload Apache

    - name: Enable recipe-book site
      ansible.builtin.command: a2ensite recipe-book.conf
      args:
        warn: false
      notify: Reload Apache

    - name: Disable default Apache site
      ansible.builtin.command: a2dissite 000-default.conf
      args:
        warn: false
      ignore_errors: yes
      notify: Reload Apache

    - name: Ensure Apache2 is running and enabled
      ansible.builtin.service:
        name: apache2
        state: started
        enabled: yes

  handlers:
    - name: Reload Apache
      ansible.builtin.service:
        name: apache2
        state: restarted
```

Основные действия playbook:

- обновляет кеш пакетов `apt`;
- ставит Apache2 и расширения PHP, необходимые для работы Laravel-подобного проекта;
- создаёт директории `/var/www/recipe-book` и `/var/www/recipe-book/public`;
- создаёт конфигурацию виртуального хоста `recipe-book.conf` с `ServerName recipe-book.local` и корнем `{{ app_public }}`;
- включает сайт `recipe-book`, отключает дефолтный `000-default.conf`;
- применяет изменения через хэндлер `Reload Apache` и гарантирует, что служба `apache2` запущена и включена в автозапуск.
  
<img width="1920" height="641" alt="{C56C0DE0-CF65-414B-9F0E-3D0BA0D68D87}" src="https://github.com/user-attachments/assets/62e95d86-e1a5-44e6-bb35-711fcff1fe52" />


### Шаг 6. Настройка и запуск Jenkins Pipeline для тестирования PHP-проекта

На этом шаге создаётся Pipeline-задача в Jenkins, которая будет выполнять автоматическую сборку и тестирование PHP-проекта с использованием PHPUnit.
Pipeline запускается на созданном ранее SSH-агенте и использует Jenkinsfile (Groovy Pipeline Script), расположенный в репозитории GitHub.

<img width="1920" height="410" alt="{35AFA02E-F78C-4F8C-A198-7295D161EE83}" src="https://github.com/user-attachments/assets/a620bfc5-b437-4dc6-b9ea-e4eff6794313" />

#### 6.1. Создание нового Pipeline-проекта в Jenkins

1. В интерфейсе Jenkins необходимо нажать **New Item**.
2. Ввести имя задачи, например:
   **`php-build-test`**
3. Выбрать тип задачи **Pipeline**.
4. Нажать **OK**.

![image](https://i.imgur.com/h2OVWkX.png)

#### 6.2. Настройка Pipeline Script From SCM

В разделе **Pipeline → Definition** выбрать:

- **Pipeline script from SCM**
- SCM: **Git**
- Repository URL:
- Branches to build:
  `*/lab05`
- Script Path:
  `lab05/pipelines/php_build_and_test_pipeline.groovy`

![image](https://i.imgur.com/BbLK1tW.png)
![image](https://i.imgur.com/8G6oNcK.png)

#### 6.3. Конфигурация Pipeline-файла

Файл `php_build_and_test_pipeline.groovy` загружается Jenkins'ом из GitHub.
Он выполняет:

- клонирование проекта;
- установку зависимостей через Composer;
- запуск PHPUnit тестов;
- архивирование результатов тестирования.

Код `Pipeline`:

```groovy
pipeline {
    agent { label 'ssh-agent' }

    environment {
        REPO_URL    = 'https://github.com/iurii1801/auto_scripting.git'
        REPO_BRANCH = 'lab05'
        PROJECT_DIR = 'lab05/recipe-book'
    }

    options {
        timestamps()
        disableConcurrentBuilds()
    }

    stages {
        stage('Checkout project') {
            steps {
                echo "Cloning auto_scripting repository (branch: ${env.REPO_BRANCH})..."
                git branch: "${env.REPO_BRANCH}", url: "${env.REPO_URL}"
            }
        }

        stage('Install Composer dependencies') {
            steps {
                echo "Installing Composer dependencies inside ${env.PROJECT_DIR}..."
                dir("${env.PROJECT_DIR}") {
                    sh '''
                        composer install --no-interaction --prefer-dist --no-progress
                    '''
                }
            }
        }

        stage('Run PHPUnit tests') {
            steps {
                echo "Running PHPUnit tests..."
                dir("${env.PROJECT_DIR}") {
                    sh '''
                        mkdir -p build/logs
                        ./vendor/bin/phpunit \
                          --colors=always \
                          --log-junit build/logs/junit.xml \
                          tests
                    '''
                }
            }
        }
    }

    post {
        always {
            echo "Archiving test reports..."
            archiveArtifacts artifacts: "${env.PROJECT_DIR}/build/logs/**/*.xml", fingerprint: true
            junit "${env.PROJECT_DIR}/build/logs/**/*.xml"
        }

        success {
            echo "Tests passed successfully!"
        }

        failure {
            echo "Tests failed! Check console output and reports."
        }
    }
}
```

![image](https://i.imgur.com/tWusZlI.png)
![image](https://i.imgur.com/SrY4OXJ.png)

#### 6.4. Запуск Pipeline

После сохранения настроек необходимо нажать:

`Build Now`

Процесс сборки включает:

- получение кода из GitHub;
- запуск контейнера ssh-agent;
- установку зависимостей Composer;
- выполнение PHPUnit тестов;
- генерацию отчёта JUnit.

![image](https://i.imgur.com/gu8pL5a.png)
![image](https://i.imgur.com/42Dl3Cb.png)

#### 6.5. Проверка отчётов о тестах

После успешного запуска:

- появится вкладка **Test Results**
- будет доступен артефакт `junit.xml`

![image](https://i.imgur.com/SAiei5j.png)

После выполнения всех этапов:

- Pipeline выполняется без ошибок
- Composer устанавливает зависимости
- PHPUnit запускает тесты
- Отчёт тестирования сохраняется как артефакт
- Jenkins показывает **SUCCESS ✓**

### Шаг 7. Конвейер для настройки тестового сервера с помощью Ansible

На этом шаге создаётся отдельный Jenkins Pipeline, который выполняет автоматическую настройку тестового сервера с помощью Ansible-агента.
Конвейер использует ранее созданный агент **ansible-agent**, подключённый по SSH и успешно запущенный (см. скриншоты подтверждения подключения).

#### 7.1. Создание файла `ansible_setup_pipeline.groovy`

В каталоге `lab05/pipelines/` необходимо создать файл `ansible_setup_pipeline.groovy`

Этот pipeline должен:

1. Клонировать GitHub-репозиторий с Ansible playbook.
2. Запустить выполнение playbook на тестовом сервере через Ansible-агент.

Содержимое файла:

```groovy
pipeline {
    agent { label 'ansible-agent' }

    environment {
        REPO_URL        = 'https://github.com/iurii1801/Auto_scripting.git'
        REPO_BRANCH     = 'lab05'
        ANSIBLE_DIR     = 'lab05/ansible'
        INVENTORY_FILE  = 'hosts.ini'
        PLAYBOOK_FILE   = 'setup_test_server.yml'
    }

    options {
        timestamps()
        disableConcurrentBuilds()
    }

    stages {

        stage('Checkout Ansible repo') {
            steps {
                echo "Cloning repository with Ansible playbook (branch: ${env.REPO_BRANCH})..."
                git branch: "${env.REPO_BRANCH}", url: "${env.REPO_URL}"
            }
        }

        stage('Run Ansible playbook') {
            steps {
                echo "Running Ansible playbook ${env.PLAYBOOK_FILE}..."
                dir("${env.ANSIBLE_DIR}") {
                    sh """
                        ansible-playbook -i ${env.INVENTORY_FILE} ${env.PLAYBOOK_FILE}
                    """
                }
            }
        }
    }

    post {
        success {
            echo "Ansible setup completed successfully!"
        }
        failure {
            echo "Ansible setup failed. Check console output for details."
        }
    }
}
```

![image](https://i.imgur.com/mZnOH7H.png)
![image](https://i.imgur.com/eHY9rYS.png)

#### 7.2. Создание нового Pipeline в Jenkins

1. В разделе Jenkins необходимо нажать **New Item**.
2. Ввести имя задачи, например:

   `ansible-agent`
3. Выбрать тип **Pipeline**.
4. Нажать **OK**.

![image](https://i.imgur.com/Shpmrwj.png)

#### 7.3. Настройка Pipeline из SCM

В разделе **Pipeline**:

- **Definition** → *Pipeline script from SCM*
- **SCM** → *Git*
- Repository URL:
- Branch:

  ```
  */lab05
  ```
- Script Path:

  ```
  lab05/pipelines/ansible_setup_pipeline.groovy
  ```

![image](https://i.imgur.com/bjHySkm.png)
![image](https://i.imgur.com/YT5DxId.png)

#### 7.4. Запуск конвейера

После сохранения необходимо нажать `Build Now`

После успешного выполнения Pipeline:

- Playbook `setup_test_server.yml` выполнится на тестовом сервере.
- Apache2 будет установлен.
- PHP-окружение будет настроено.
- Виртуальный хост `recipe-book` будет активирован.
- Тестовый сервер будет полностью готов.

![image](https://i.imgur.com/7BNa46h.png)

### Шаг 8. Конвейер для размещения PHP-проекта на тестовом сервере

На этом шаге создаётся отдельный Jenkins-конвейер, задача которого — **развернуть PHP-приложение на тестовом сервере** с помощью Ansible, используя ранее созданный `ansible-agent`.

Конвейер выполняет:

- клонирование репозитория с PHP-проектом;
- передачу файлов проекта на тестовый сервер;
- выполнение Ansible playbook для установки и настройки тестового окружения.

#### 8.1. Создание файла `php_deploy_pipeline.groovy`

В папке `lab05/pipelines/` необходимо создать новый файл `php_deploy_pipeline.groovy` и поместить туда следующий pipeline-скрипт:

```groovy
pipeline {
    agent { label 'ansible-agent' }

    environment {
        REPO_URL       = 'https://github.com/iurii1801/Auto_scripting.git'
        REPO_BRANCH    = 'lab05'
        ANSIBLE_DIR    = 'lab05/ansible'
        INVENTORY_FILE = 'hosts.ini'
        PLAYBOOK_FILE  = 'deploy_recipe_book.yml'
    }

    options {
        timestamps()
        disableConcurrentBuilds()
    }

    stages {

        stage('Checkout repo with PHP project') {
            steps {
                echo "Cloning repository with PHP project (branch: ${env.REPO_BRANCH})..."
                git branch: "${env.REPO_BRANCH}", url: "${env.REPO_URL}"
            }
        }

        stage('Deploy PHP project to test server') {
            steps {
                echo "Running Ansible deploy playbook ${env.PLAYBOOK_FILE}..."
                dir("${env.ANSIBLE_DIR}") {
                    sh """
                        ansible-playbook -i ${env.INVENTORY_FILE} ${env.PLAYBOOK_FILE}
                    """
                }
            }
        }
    }

    post {
        success {
            echo "PHP project has been successfully deployed to the test server."
        }
        failure {
            echo "Deployment failed. Check console output and Ansible logs."
        }
    }
}
```

![image](https://i.imgur.com/GiF5kLs.png)
![image](https://i.imgur.com/onnAnlx.png)

#### 8.2. Ansible playbook, выполняющий деплой PHP проекта

В папке `lab05/ansible/` был создан файл `deploy_recipe_book.yml`, содержащий следующий playbook:

```yml
---
- name: Deploy recipe-book PHP application to test server
  hosts: test_server
  become: yes
  become_user: root

  vars:
    app_root: /var/www/recipe-book
    repo_url: 'https://github.com/iurii1801/Auto_scripting.git'
    repo_branch: 'lab05'
    src_dir: /opt/auto_scripting

  tasks:
    - name: Ensure git is installed
      ansible.builtin.apt:
        name: git
        state: present
        update_cache: yes

    - name: Create source directory
      ansible.builtin.file:
        path: "{{ src_dir }}"
        state: directory
        mode: "0755"

    - name: Clone Auto_scripting repository with PHP project
      ansible.builtin.git:
        repo: "{{ repo_url }}"
        dest: "{{ src_dir }}"
        version: "{{ repo_branch }}"
        force: yes

    - name: Copy recipe-book application to Apache docroot
      ansible.builtin.copy:
        src: "{{ src_dir }}/lab05/recipe-book/"
        dest: "{{ app_root }}/"
        owner: www-data
        group: www-data
        mode: "0755"
        remote_src: yes
      notify: Reload Apache

    - name: Ensure correct permissions on app root
      ansible.builtin.file:
        path: "{{ app_root }}"
        state: directory
        owner: www-data
        group: www-data
        recurse: yes
    
    - name: Ensure Apache is installed
      ansible.builtin.apt:
       name: apache2
       state: present
       update_cache: yes

    - name: Ensure Apache is running
      ansible.builtin.service:
       name: apache2
       state: started
       enabled: yes
       
  handlers:
  - name: Reload Apache
    ansible.builtin.service:
      name: apache2
      state: restarted
```

![image](https://i.imgur.com/SZCSRHL.png)
![image](https://i.imgur.com/qGiPaU1.png)

Playbook выполняет:

- установку git;
- клонирование PHP-проекта;
- копирование в `/var/www/recipe-book`;
- установку Apache;
- перезапуск Apache.

#### 8.3. Создание нового Pipeline в Jenkins

1. Необходимо открыть **Jenkins** → **New Item**
2. Ввести имя: **php-deploy**
3. Выбрать тип: **Pipeline**

![image](https://i.imgur.com/qZmzITp.png)

#### 8.4. Настройка Pipeline через SCM

В настройках:

1. Необходимо перейти в раздел **Pipeline**
2. Выбрать источник: **Pipeline script from SCM**
3. SCM → **Git**
4. Repository URL:
5. Branch Specifier:

```
*/lab05
```

6. Script Path:

```
lab05/pipelines/php_deploy_pipeline.groovy
```

![image](https://i.imgur.com/oRxuxMl.png)
![image](https://i.imgur.com/Fn9m7DG.png)

После этого необходимо нажать **Save**.

#### 8.5. Запуск конвейера

Необходимо нажать **Build Now**.

#### 8.6. Вывод на консоль (успешный)

В **Console Output** видно:

- репозиторий был успешно склонирован;
- запущен Ansible playbook;
- ошибок нет.

<img width="1226" height="176" alt="{F4AE93CD-17C1-429E-992D-CBB5D7BCDFEC}" src="https://github.com/user-attachments/assets/5cc0a397-052f-4028-a479-cb6a33b7fae5" />
![image](https://i.imgur.com/1an9tv0.png)

### Шаг 9. Тестирование размещенного PHP-проекта

После выполнения всех трёх конвейеров Jenkins необходимо проверить, что PHP-приложение **recipe-book**, развернутое с помощью Ansible на контейнере **test-server**, корректно работает и доступно пользователю через браузер.

Для этого в `docker-compose.yaml` необходимо добавить проброс порта веб-сервера Apache:

```yaml
ports:
  - "2224:22"
  - "8088:80"
```

Благодаря этому контейнер test-server становится доступен на хост-машине по адресу `http://localhost:8088`.

#### 9.1. Проверка статуса контейнеров

Сначала необходимо проверить, что все необходимые контейнеры запущены:

```bash
docker ps
```

В выводе должны быть контейнеры:

- `jenkins-controller`
- `ssh-agent`
- `ansible-agent`
- контейнер с PHP-приложением, опубликованный на порту **8088 → 80**.

![image](https://i.imgur.com/ijC2t33.png)

#### 9.2. Открытие приложения в браузере

Далее необходимо открыть веб-браузер на хост-машине и перейти по адресу:

```
http://localhost:8088
```

Этот адрес соответствует контейнеру **test-server**, на котором Ansible:

- установил Apache и PHP,
- создал директорию `/var/www/recipe-book`,
- скопировал в неё файлы проекта,
- установил правильные владельцев и права,
- активировал виртуальный хост,
- перезапустил Apache.

После загрузки страницы отобразится главная страница приложения:

- заголовок **«Последние рецепты»**
- текст «Пока нет рецептов», если база пустая
- ссылки **«Добавить новый рецепт»** и **«Все рецепты»**

<img width="580" height="123" alt="{22FBF134-3042-4F5A-84EA-601B5A8ADF42}" src="https://github.com/user-attachments/assets/879c76ac-299d-4a58-b6c9-028be66cb528" />

Это означает:

- Apache на тестовом сервере работает корректно
- PHP-файлы приложения выполняются успешно
- директория `/var/www/recipe-book` настроена правильно
- Ansible playbook отработал без ошибок
- деплой через Jenkins завершился успешно

---

# Контрольные вопросы и ответы

## 1. Преимущества использования Ansible для настройки серверов

Ansible позволяет полностью автоматизировать процесс настройки серверов, делая его предсказуемым и повторяемым.  
Главные преимущества:

- управление по SSH, без установки агентов на удалённые машины;
- декларативные, понятные playbook'и;
- единообразная настройка как одного сервера, так и целых групп;
- минимизация человеческих ошибок;
- идемпотентность — изменения применяются только если это действительно нужно.

Благодаря этому Ansible делает управление инфраструктурой более надёжным и удобным.

---

## 2. Основные модули Ansible

Ansible включает множество встроенных модулей, покрывающих все этапы настройки системы:

- **Пакеты:** `apt`, `yum`, `dnf`, `pip`
- **Файлы:** `copy`, `template`, `file`, `unarchive`
- **Сервисы:** `service`, `systemd`
- **Пользователи:** `user`, `group`
- **Сетевые настройки:** `ufw`, `iptables`, `firewalld`
- **Облачные платформы:** AWS, Azure, GCP
- **Docker:** `docker_container`, `docker_image`, `docker_volume`

Такой набор позволяет полностью автоматизировать развертывание и сопровождение серверов.

---

## 3. Проблемы при создании Ansible playbook и их решение

В ходе работы возникли несколько типичных проблем и были успешно решены:

1. **Ошибки SSH-подключения.**  
   *Решение:* корректная генерация ключей, настройки прав (`chmod 600`), указание пользователя и ключа в inventory.

2. **Проблемы с правами при копировании файлов.**  
   *Решение:* настройка владельца (`www-data`) и прав через модуль `file`.

3. **Некорректные пути к директориям проекта.**  
   *Решение:* явное указание `src_dir`, `app_root` и использование `remote_src: yes`.

4. **Apache не перезапускался после деплоя.**  
   *Решение:* добавлен handler `Reload Apache`, вызов через `notify`.

После исправлений playbook стал стабильно выполнять все задачи, а процесс деплоя стал предсказуемым и полностью автоматизированным.

---

# Вывод

В рамках лабораторной работы была создана полноценная автоматизированная инфраструктура для развертывания PHP-приложения с использованием Docker, Jenkins и Ansible.

Были выполнены следующие задачи:

- подняты контейнеры Jenkins, Ansible, тестовый сервер и SSH-агент;
- настроено SSH-взаимодействие между узлами;
- подготовлены Jenkins-пайплайны для автоматического тестирования и деплоя;
- написан Ansible playbook для автоматической конфигурации веб-сервера;
- произведён автоматический деплой проекта `recipe-book` на тестовый сервер.

После выполнения всех этапов приложение стало доступно в браузере, что подтверждает правильность настроек.  
Работа показала, что сочетание Jenkins + Ansible + Docker позволяет выстроить удобный, гибкий и воспроизводимый CI/CD-процесс.

---

# Библиография

1. Jenkins Documentation — официальная документация Jenkins.  
2. Jenkins Docker Hub — официальный Docker-образ Jenkins.  
3. SSH Build Agents Plugin — документация плагина для SSH-агентов Jenkins.  
4. Jenkins Pipeline Syntax — руководство по синтаксису pipeline.  
5. Docker Documentation — документация Docker.  
6. Docker Compose Documentation — создание многоконтейнерных приложений.  
7. Ansible Documentation — полное руководство по Ansible.  
8. Ansible Built-in Modules — справочник встроенных модулей Ansible.
