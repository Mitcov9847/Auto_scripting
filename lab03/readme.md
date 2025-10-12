# lab03 — Cron automation in Docker

Files:
- currency_exchange_rate.py — script to run
- cronjob — cron schedule (copied to /etc/cron.d)
- entrypoint.sh — creates log file, starts tail and cron
- Dockerfile — builds image with cron
- docker-compose.yml — builds and runs the container
- logs/cron.log — host-mounted log file

Build & run:
1. docker compose build
2. docker compose up

Check logs:
- On host: ./logs/cron.log
- Or: docker exec -it lab03_cron tail -n 100 /var/log/cron.log
