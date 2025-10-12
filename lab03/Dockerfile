FROM python:3.11-slim

# Install cron and bash utilities
RUN apt-get update && apt-get install -y cron && rm -rf /var/lib/apt/lists/*

WORKDIR /app

# Copy application files
COPY currency_exchange_rate.py /app/currency_exchange_rate.py
COPY cronjob /etc/cron.d/cronjob
COPY entrypoint.sh /entrypoint.sh

# Make scripts executable and set permissions for cron file
RUN chmod +x /entrypoint.sh \
    && chmod 0644 /etc/cron.d/cronjob \
    && ln -s /etc/cron.d/cronjob /etc/cron.d/cronjob.backup || true

# (Опционально) Install python deps here, если нужны:
# RUN pip install requests

# Ensure cron reads /etc/cron.d files (cron reads them automatically)
# Provide a small sleep to allow env file creation if needed (not mandatory)

ENTRYPOINT ["/entrypoint.sh"]
