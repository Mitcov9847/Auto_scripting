#!/bin/sh

create_log_file() {
    echo "Creating log file..."
    touch /var/log/cron.log
    chmod 666 /var/log/cron.log
    echo "Log file created at /var/log/cron.log"
}

monitor_logs() {
    echo "=== Monitoring cron logs ==="
    tail -f /var/log/cron.log &
}

run_cron() {
    echo "=== Starting cron daemon ==="
    exec cron -f
}

# Export environment for cron jobs
env > /etc/environment

create_log_file
monitor_logs
run_cron
