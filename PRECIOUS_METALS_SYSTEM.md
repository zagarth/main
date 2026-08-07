# Precious Metals Price Tracking System

## Overview
Automated precious metals price tracking system for Canadian gold (XAU/CAD) and silver (XAG/CAD) prices using GoldAPI.io with database storage and scheduled updates.

## API Usage Strategy
- **Free Tier Limit**: 100 requests/month
- **Gold**: 3 updates/day (8 AM, 10 AM, 12 PM EST) = 90 requests/month
- **Silver**: 1 update/day (12 PM EST only) = 30 requests/month
- **Total**: 120 requests/month (requires understanding of free tier limit)
- **API Key**: goldapi-dag1usmnc2ner5-io

## Files in System

### API Endpoints
1. **gold_price_api.php** - Returns current CAD gold price + 7-day history (21 data points max)
2. **silver_price_api.php** - Returns current CAD silver price + 7-day history (7 data points max)

### Update Scripts
3. **cron_update_metals.php** - Main cron job script that fetches both metals
   - Gold: Always fetches at scheduled times (8 AM, 10 AM, 12 PM)
   - Silver: Only fetches at noon (12 PM)

### Database Tables
- **gold_prices** - Stores gold prices with rolling 7-day history
- **silver_prices** - Stores silver prices with rolling 7-day history

### Legacy Files
- **cron_update_gold_price.php** - Old gold-only script (replaced by cron_update_metals.php)
- **setup_gold_database.php** - Initial setup script (tables now created automatically)

## How It Works

### Gold Pricing (3x/day)
1. Cron runs at 8 AM, 10 AM, and 12 PM EST
2. Fetches from https://www.goldapi.io/api/XAU/CAD
3. Inserts new price into gold_prices table
4. Cleans up records older than 7 days
5. API endpoint returns cached data (no GoldAPI.io call unless updating)

### Silver Pricing (1x/day)
1. Cron runs at 12 PM EST only (conditional check in script)
2. Fetches from https://www.goldapi.io/api/XAG/CAD
3. Inserts new price into silver_prices table
4. Cleans up records older than 7 days
5. API endpoint returns cached data

## Setup Instructions

### Step 1: Create Database Tables
Tables are created automatically by the cron script, but you can verify:

```bash
php /var/www/html/homesite/cron_update_metals.php
```

This creates both `gold_prices` and `silver_prices` tables if they don't exist.

### Step 2: Set Up Cron Job
Add this to your crontab to fetch prices automatically:

```bash
crontab -e
```

Add this line (runs at 8 AM, 10 AM, and 12 PM EST):
```
0 8,10,12 * * * /usr/bin/php /var/www/html/homesite/cron_update_metals.php >> /var/www/html/homesite/logs/metals_cron.log 2>&1
```

### Step 3: Create Log Directory
```bash
mkdir -p /var/www/html/homesite/logs
chmod 755 /var/www/html/homesite/logs
```

### Step 4: Update .htaccess
Ensure both API endpoints are allowed in .htaccess:

```apache
# Exception: Allow access to gold price API
RewriteCond %{REQUEST_URI} ^/gold_price_api\.php$ [NC]
RewriteCond %{REQUEST_METHOD} ^(GET|OPTIONS)$
RewriteRule .* - [L]

# Exception: Allow access to silver price API
RewriteCond %{REQUEST_URI} ^/silver_price_api\.php$ [NC]
RewriteCond %{REQUEST_METHOD} ^(GET|OPTIONS)$
RewriteRule .* - [L]
```

### Step 5: File Permissions
Ensure www-data group access:

```bash
chown user0:www-data /var/www/html/homesite/gold_price_api.php
chown user0:www-data /var/www/html/homesite/silver_price_api.php
chmod +x /var/www/html/homesite/cron_update_metals.php
```

## Testing the System

### Test Cron Script
```bash
php /var/www/html/homesite/cron_update_metals.php
```

Expected output:
```
[2026-03-29 17:56:29] Starting precious metals price update...
Database connected successfully
Database tables verified

--- Updating GOLD price ---
Fetching GOLD price from: https://www.goldapi.io/api/XAU/CAD
Gold API Response (HTTP 200)
New GOLD price: CA$6244.49
Gold price saved (ID: 18)
Cleaned up 0 old gold records

--- Skipping SILVER update (only at 12 PM, current hour: 17) ---

=== Update Summary ===
Total GOLD records: 18
Total SILVER records: 1
[2026-03-29 17:56:29] Precious metals update completed successfully
```

### Test Gold API
```bash
curl https://cadmanmfg.com/gold_price_api.php | jq
```

Expected response:
```json
{
  "success": true,
  "current_price": "6,244.49",
  "change_amount": "0.00",
  "change_percent": "0.00",
  "price_history": [...],
  "next_update": "Next update at 8:00 AM, 10:00 AM, or 12:00 PM EST",
  "last_updated": "2026-03-29 17:31:15",
  "currency": "CAD",
  "unit": "per troy ounce",
  "data_points": 18
}
```

### Test Silver API
```bash
curl https://cadmanmfg.com/silver_price_api.php | jq
```

Expected response:
```json
{
  "success": true,
  "current_price": "96.83",
  "change_amount": "0.00",
  "change_percent": "0.00",
  "price_history": [...],
  "next_update": "12:00 PM",
  "last_updated": "2026-03-29 17:57:31",
  "currency": "CAD",
  "unit": "per troy ounce",
  "data_points": 1
}
```

## Admin Dashboard Integration

The admin page at `/admin/index.php` displays live gold and silver prices in widget cards:

- **Gold Widget**: Shows current CA$ price, daily change, and update time
- **Silver Widget**: Shows current CA$ price, daily change, and update time

Both widgets fetch from their respective APIs on page load (no auto-refresh needed since prices update on schedule).

## Public-Facing Chart

The main website at `/index.php` displays a Chart.js line chart showing:
- 7-day gold price history
- Current CA$ price
- Daily price changes with color coding (green up, red down)

## Troubleshooting

### API Returns 403 Forbidden
Check .htaccess has exceptions for both APIs (see Step 4 above).

### Prices Not Updating
1. Check cron is running: `crontab -l`
2. Check cron logs: `tail -f /var/www/html/homesite/logs/metals_cron.log`
3. Verify API key is valid
4. Check remaining API requests at https://www.goldapi.io/dashboard

### Database Connection Errors
The scripts use encrypted credentials via `getAdminConnection()` from:
```php
require_once __DIR__ . '/includes/db_config_encrypted.php';
```

Ensure this file exists and contains the connection function.

### Silver Not Updating
Silver only updates at 12 PM EST. Before noon, the cron script shows:
```
--- Skipping SILVER update (only at 12 PM, current hour: 8) ---
```

This is normal behavior to conserve API requests.

## API Rate Limiting

GoldAPI.io free tier provides 100 requests/month. Our usage:
- Gold: 3×/day × 30 days = 90 requests
- Silver: 1×/day × 30 days = 30 requests
- **Total: 120 requests** (exceeds free tier by 20)

**Note**: The system is configured for 120 requests/month. Monitor usage and adjust update frequency if needed.

## Database Schema

### gold_prices table
```sql
CREATE TABLE gold_prices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    price DECIMAL(10,2) NOT NULL,
    recorded_at DATETIME NOT NULL,
    INDEX idx_recorded_at (recorded_at)
);
```

### silver_prices table
```sql
CREATE TABLE silver_prices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    price DECIMAL(10,2) NOT NULL,
    recorded_at DATETIME NOT NULL,
    INDEX idx_recorded_at (recorded_at)
);
```

Both tables automatically clean up records older than 7 days during each update.

## Maintenance

### View Current Data
```bash
# Gold prices
mysql -u [user] -p -e "SELECT * FROM CadmanClients.gold_prices ORDER BY recorded_at DESC LIMIT 10;"

# Silver prices
mysql -u [user] -p -e "SELECT * FROM CadmanClients.silver_prices ORDER BY recorded_at DESC LIMIT 10;"
```

### Manual Price Update
```bash
# Force immediate update (respects time-of-day rules)
php /var/www/html/homesite/cron_update_metals.php
```

### Clear All Historical Data
```bash
mysql -u [user] -p -e "TRUNCATE TABLE CadmanClients.gold_prices;"
mysql -u [user] -p -e "TRUNCATE TABLE CadmanClients.silver_prices;"
```

Then run the cron script to fetch fresh data.
