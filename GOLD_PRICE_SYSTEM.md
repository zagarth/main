# Gold Price Tracking System

## Overview
This system automatically fetches live Canadian gold prices from GoldAPI.io 3 times per day and maintains a rolling 7-day history in the database.

## Files Created

1. **gold_price_api.php** - API endpoint that returns current gold price and 7-day history
2. **cron_update_gold_price.php** - Cron job script to fetch prices automatically
3. **setup_gold_database.php** - One-time setup script to initialize database with historical data

## Setup Instructions

### Step 1: Initialize Database
Run this once to populate the database with your historical data:

```bash
php /var/www/html/homesite/setup_gold_database.php
```

This will create the `gold_prices` table and insert your Monday-Friday historical data (15 records with 3x daily prices).

### Step 2: Set Up Cron Job
Add this to your crontab to fetch prices automatically at 8 AM, 10 AM, and 12 PM EST:

```bash
crontab -e
```

Add this line:
```
0 8,10,12 * * * /usr/bin/php /var/www/html/homesite/cron_update_gold_price.php >> /var/www/html/homesite/logs/gold_cron.log 2>&1
```

### Step 3: Create Log Directory
```bash
mkdir -p /var/www/html/homesite/logs
chmod 755 /var/www/html/homesite/logs
```

### Step 4: Test the System

Test the cron job manually:
```bash
php /var/www/html/homesite/cron_update_gold_price.php
```

Test the API endpoint:
```bash
curl https://cadmanmfg.com/gold_price_api.php | jq
```

Or visit: https://cadmanmfg.com/gold_price_api.php

## How It Works

### Automatic Updates
- Runs 3x per day at 8 AM, 10 AM, and 12 PM EST
- Fetches live CAD gold price from GoldAPI.io
- Stores price with timestamp in database
- Automatically deletes data older than 7 days
- Rolling window maintains 21 data points (7 days × 3 updates/day)

### API Response Format
```json
{
  "success": true,
  "current_price": "6304.25",
  "change_amount": "-589.40",
  "change_percent": "-8.55",
  "price_history": [
    {
      "price": "6975.70",
      "date": "2026-03-24 09:00:00",
      "day": "Monday"
    },
    ...
  ],
  "next_update": "10:00 AM",
  "last_updated": "2026-03-29 08:00:15",
  "currency": "CAD",
  "unit": "per troy ounce",
  "data_points": 21
}
```

### Database Schema
```sql
CREATE TABLE gold_prices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    price DECIMAL(10,2) NOT NULL,
    recorded_at DATETIME NOT NULL,
    INDEX idx_recorded_at (recorded_at)
)
```

## Configuration

### GoldAPI.io API Key
Update the API key in both files:
- `gold_price_api.php` (line 12)
- `cron_update_gold_price.php` (line 17)

Your current key: `goldapi-dag1usmnc2ner5-io`

### Update Schedule
Modify the schedule in:
- `gold_price_api.php` line 15: `$updateHoursEST = [8, 10, 12];`
- Crontab entry: `0 8,10,12 * * *`

## Monitoring

### View Cron Logs
```bash
tail -f /var/www/html/homesite/logs/gold_cron.log
```

### Check Database Records
```bash
mysql -u your_username -p your_database -e "SELECT * FROM gold_prices ORDER BY recorded_at DESC LIMIT 10;"
```

### Manual Price Update
```bash
php /var/www/html/homesite/cron_update_gold_price.php
```

## API Rate Limits
- GoldAPI.io free tier: 100 requests/month
- Our usage: 3 updates/day × 30 days = ~90 requests/month
- Stays within free tier limits ✓

## Troubleshooting

### Cron not running?
Check cron service:
```bash
sudo systemctl status cron
```

### API errors?
Check the error log:
```bash
tail -20 /var/www/html/homesite/logs/gold_cron.log
```

### Database connection issues?
Verify credentials in `mail_config.php`:
- Database host
- Database name
- Username/password

## Future Enhancements

1. **Email alerts** when gold price changes significantly
2. **Weekly reports** summarizing price trends
3. **Historical data export** to CSV
4. **Chart improvements** with zoom and pan features
5. **Price predictions** using trend analysis
