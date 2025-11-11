# Dockerfile
FROM jenkins/ssh-agent:latest

# Скопировать ключ внутрь контейнера
COPY jenkins_agent_ssh_key /home/jenkins/agent/jenkins_agent_ssh_key

# Установить права и владельца
RUN chown jenkins:jenkins /home/jenkins/agent/jenkins_agent_ssh_key \
    && chmod 600 /home/jenkins/agent/jenkins_agent_ssh_key
