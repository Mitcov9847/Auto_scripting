FROM jenkins/ssh-agent:latest

COPY jenkins_agent_ssh_key /home/jenkins/agent/jenkins_agent_ssh_key

RUN chown jenkins:jenkins /home/jenkins/agent/jenkins_agent_ssh_key \
    && chmod 600 /home/jenkins/agent/jenkins_agent_ssh_key
