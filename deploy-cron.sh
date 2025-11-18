#!/bin/bash
# Cron script to check for deployment triggers and execute deployment
# Run this as root via cron: * * * * * /home/stats/deploy-cron.sh

TRIGGER_FILE="/home/stats/logs/webhook-trigger"
DEPLOY_SCRIPT="/home/stats/deploy.sh"
LOG_FILE="/home/stats/logs/webhook-deploy.log"

# Check if trigger file exists
if [ -f "$TRIGGER_FILE" ]; then
    # Log that we're processing the trigger
    echo "[$(date '+%Y-%m-%d %H:%i:%s')] Cron: Processing deployment trigger..." >> "$LOG_FILE"
    
    # Remove trigger file first to prevent multiple executions
    rm -f "$TRIGGER_FILE"
    
    # Execute deployment script
    bash "$DEPLOY_SCRIPT" >> /home/stats/logs/deploy-output.log 2>&1
    
    # Log completion
    echo "[$(date '+%Y-%m-%d %H:%i:%s')] Cron: Deployment script executed." >> "$LOG_FILE"
fi

