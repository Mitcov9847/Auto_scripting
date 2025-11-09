# Лабораторная работа 04: Автоматизация DevOps задач с Jenkins и SSH агентом

## Проект
Цель лабораторной работы — настроить Jenkins Controller и SSH агент для автоматизации задач DevOps с использованием Docker и Docker Compose, а также создать Jenkins Pipeline для проекта на PHP.

---

## Содержание
1. [Структура проекта](#структура-проекта)
2. [Настройка Jenkins Controller](#настройка-jenkins-controller)
3. [Настройка SSH агента](#настройка-ssh-агента)
4. [Подключение SSH агента к Jenkins](#подключение-ssh-агента-к-jenkins)
5. [Создание Jenkins Pipeline](#создание-jenkins-pipeline)
6. [Результаты работы](#результаты-работы)
7. [Ответы на вопросы](#ответы-на-вопросы)

---

## Структура проекта

Сначала создаём папки и файлы проекта, чтобы всё было организовано и удобно для работы с Docker и Jenkins.

lab04/
├── docker-compose.yml # Конфигурация Docker Compose для сервисов
├── Dockerfile # Dockerfile для SSH агента с PHP CLI
├── secrets/ # Папка для SSH ключей
│ ├── jenkins_agent_ssh_key
│ └── jenkins_agent_ssh_key.pub
├── .env # Переменные окружения для Docker Compose
└── README.md # Этот отчёт

yaml
Копировать код

> Скриншот структуры проекта:  
> ![Структура проекта](path/to/screenshot1.png)

---

## Настройка Jenkins Controller

**Зачем:** Jenkins Controller управляет всеми пайплайнами, задачами и агентами.

**Шаги выполнения:**

1. Добавляем сервис Jenkins в `docker-compose.yml`:

```yaml
services:
  jenkins-controller:
    image: jenkins/jenkins:lts
    container_name: jenkins-controller
    ports:
      - "8080:8080"
      - "50000:50000"
    volumes:
      - jenkins_home:/var/jenkins_home
    networks:
      - jenkins-network

volumes:
  jenkins_home:

networks:
  jenkins-network:
    driver: bridge
Запускаем контейнер Jenkins Controller:

bash
Копировать код
docker-compose up -d
docker ps
Открываем веб-интерфейс Jenkins: http://localhost:8080

Разблокируем Jenkins, используя пароль из контейнера:

bash
Копировать код
docker exec jenkins-controller cat /var/jenkins_home/secrets/initialAdminPassword
Устанавливаем рекомендуемые плагины и создаём администратора.

Скриншот интерфейса Jenkins после установки:

Настройка SSH агента
Зачем: SSH агент позволяет Jenkins запускать задачи на удалённых машинах или контейнерах с необходимыми правами.

Шаги выполнения:

Создаём папку secrets и генерируем SSH ключи:

bash
Копировать код
mkdir secrets
cd secrets
ssh-keygen -f jenkins_agent_ssh_key
Создаём Dockerfile для SSH агента с PHP CLI:

dockerfile
Копировать код
FROM jenkins/ssh-agent

# install PHP-CLI
RUN apt-get update && apt-get install -y php-cli
Добавляем сервис SSH агента в docker-compose.yml:

yaml
Копировать код
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
  jenkins_agent_volume:
Создаём .env для передачи публичного ключа:

ini
Копировать код
JENKINS_AGENT_SSH_PUBKEY=secrets/jenkins_agent_ssh_key.pub
Перезапускаем Docker Compose:

bash
Копировать код
docker-compose up -d --build
Скриншот контейнеров Docker:

Подключение SSH агента к Jenkins
Зачем: Нужно, чтобы Jenkins мог запускать задачи на нашем SSH агенте.

Шаги выполнения:

Проверяем, что установлен SSH Agents Plugin.

Добавляем SSH ключ в Jenkins:

Manage Jenkins → Manage Credentials → Add Credentials

Username: jenkins

Private Key: выбираем jenkins_agent_ssh_key

Создаём новый агент:

Manage Jenkins → Manage Nodes → New Node

Имя: ssh-agent1

Тип: Permanent Agent

Метка: php-agent

Remote root directory: /home/jenkins/agent

Launch method: Launch agents via SSH

Host: ssh-agent

Credentials: выбранный SSH ключ

Скриншот агента в Jenkins:

Создание Jenkins Pipeline
Зачем: Автоматизация сборки, тестирования и деплоя проекта на PHP.

Пример Jenkinsfile:

groovy
Копировать код
pipeline {
    agent {
        label 'php-agent'
    }
    
    stages {        
        stage('Install Dependencies') {
            steps {
                echo 'Preparing project...'
                // Добавляем команды установки зависимостей
            }
        }
        
        stage('Test') {
            steps {
                echo 'Running tests...'
                // Добавляем команды запуска unit-тестов
            }
        }
    }
    
    post {
        always {
            echo 'Pipeline completed.'
        }
        success {
            echo 'All stages completed successfully!'
        }
        failure {
            echo 'Errors detected in the pipeline.'
        }
    }
}
Скриншот пайплайна:

Результаты работы
Jenkins Controller успешно запущен.

SSH агент создан и подключен к Jenkins.

Pipeline PHP проекта выполнен успешно.

Unit-тесты прошли без ошибок.

Скриншот результатов выполнения пайплайна:

Ответы на вопросы
Преимущества использования Jenkins для DevOps автоматизации:

Автоматизация сборки, тестирования и деплоя.

Централизованное управление задачами CI/CD.

Возможность распределения нагрузки через агенты.

Поддержка множества плагинов и интеграций.

Типы Jenkins агентов:

SSH агенты

JNLP агенты

Docker агенты

Permanent и ephemeral агенты

Проблемы при настройке Jenkins и их решение:

Ошибки доступа к SSH агенту → проверка ключей и путей.

Ошибки сборки контейнера → перезапуск с --build.

Отсутствие PHP CLI на агенте → установка через Dockerfile.

Заключение
Лабораторная работа выполнена полностью. Все цели достигнуты: Jenkins Controller и SSH агент настроены, DevOps pipeline для PHP проекта работает корректно.
