# ParamAds - CyberPanel Deployment Guide

## Prerequisites

- CyberPanel installed on your server
- PHP 8.1+ with extensions: pdo_mysql, mbstring, xml, curl, zip, gd, bcmath, json, openssl
- MySQL 8.0+ or MariaDB 10.6+
- Composer installed globally
- A domain pointed to your server

---

## Method 1: Automated Deployment (Recommended)

### Step 1: Upload Files
Upload the `paramads` project folder to your server (e.g., `/root/paramads`).

### Step 2: Run Deployment Script
```bash
cd /root/paramads
sudo bash cyberpanel-deploy.sh
```

The script will:
- Check and install prerequisites
- Copy files to your document root
- Configure environment variables
- Set permissions
- Configure OpenLiteSpeed vHost
- Set up cron jobs and queue workers
- Restart the web server

### Step 3: Run Web Installer
Visit `https://yourdomain.com/install` and follow the steps.

---

## Method 2: Manual Deployment

### Step 1: Create Website in CyberPanel

1. Log in to CyberPanel at `https://your-server-ip:8090`
2. Go to **Websites > Create Website**
3. Enter your domain name
4. Select PHP version **8.1** or higher
5. Click **Create Website**

### Step 2: Create Database

1. Go to **Databases > Create Database**
2. Enter database name: `paramads`
3. Enter database user: `paramads_user`
4. Set a strong password
5. Click **Create Database**

### Step 3: Upload Files

#### Option A: Via File Manager
1. Go to **Websites > List Websites > File Manager**
2. Navigate to `public_html`
3. Upload the `backend` folder contents

#### Option B: Via SSH
```bash
# SSH into your server
ssh root@your-server-ip

# Navigate to document root
cd /home/yourdomain.com/public_html

# Upload and extract files
# (upload paramads.zip via SCP or SFTP first)
unzip paramads.zip
cp -r paramads/backend/* .
cp paramads/backend/.env.example .env
```

### Step 4: Install Dependencies

```bash
cd /home/yourdomain.com/public_html
composer install --no-dev --optimize-autoloader
```

### Step 5: Configure Environment

```bash
# Generate application key
php artisan key:generate

# Edit .env file
nano .env
```

Update these values in `.env`:
```env
APP_URL=https://yourdomain.com
APP_ENV=production
APP_DEBUG=false

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=paramads
DB_USERNAME=paramads_user
DB_PASSWORD=your_password
```

### Step 6: Set Document Root

**IMPORTANT:** The document root must point to the `public` directory.

1. Go to CyberPanel > **Websites > List Websites**
2. Click on your domain
3. Go to **vHost Conf**
4. Update the `docRoot` to: `/home/yourdomain.com/public_html/public`
5. Add rewrite rules:

```
context / {
  location                /home/yourdomain.com/public_html/public
  allowBrowse             1
  rewrite  {
    enable                1
    autoLoadHtaccess      1
  }
}
```

6. Click **Save** and restart OpenLiteSpeed

### Step 7: Set Permissions

```bash
cd /home/yourdomain.com/public_html

# Set ownership
chown -R nobody:nobody .

# Set directory permissions
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;

# Writable directories
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

### Step 8: Set Up Cron Job

```bash
# Add Laravel scheduler
crontab -e

# Add this line:
* * * * * cd /home/yourdomain.com/public_html && php artisan schedule:run >> /dev/null 2>&1
```

### Step 9: Set Up Queue Worker

```bash
# Install supervisor
apt install supervisor -y

# Create config
nano /etc/supervisor/conf.d/paramads-worker.conf
```

Add:
```ini
[program:paramads-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /home/yourdomain.com/public_html/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=nobody
numprocs=2
redirect_stderr=true
stdout_logfile=/home/yourdomain.com/public_html/storage/logs/worker.log
```

```bash
supervisorctl reread
supervisorctl update
supervisorctl start paramads-worker:*
```

### Step 10: SSL Certificate

1. Go to CyberPanel > **SSL > Manage SSL**
2. Select your domain
3. Click **Issue SSL**

Or via command line:
```bash
certbot --webroot -w /home/yourdomain.com/public_html/public -d yourdomain.com
```

### Step 11: Run Web Installer

Visit `https://yourdomain.com/install` and follow the setup wizard:

1. **System Check** - Verifies all requirements
2. **Database Setup** - Enter your database credentials
3. **API Keys** - Configure Meta Ads, Google Ads, Stripe keys
4. **Admin Account** - Create your admin user
5. **Complete** - Installation done!

---

## Setting Up the AI Service (Optional)

The Python AI microservice provides forecasting, anomaly detection, and NL insights.

### Install Python Dependencies

```bash
cd /root/paramads/ai-service
pip3 install -r requirements.txt
```

### Run as a Service

```bash
# Create systemd service
cat > /etc/systemd/system/paramads-ai.service << EOF
[Unit]
Description=ParamAds AI Service
After=network.target

[Service]
Type=simple
User=root
WorkingDirectory=/root/paramads/ai-service
ExecStart=/usr/bin/python3 -m uvicorn main:app --host 0.0.0.0 --port 8001
Restart=always

[Install]
WantedBy=multi-user.target
EOF

systemctl enable paramads-ai
systemctl start paramads-ai
```

Update `.env` in your Laravel app:
```env
AI_SERVICE_URL=http://127.0.0.1:8001
```

---

## Troubleshooting

### 500 Internal Server Error
```bash
# Check Laravel logs
tail -f /home/yourdomain.com/public_html/storage/logs/laravel.log

# Ensure storage is writable
chmod -R 775 storage bootstrap/cache
```

### Blank Page
```bash
# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

### Database Connection Error
- Verify credentials in `.env`
- Ensure MySQL is running: `systemctl status mysql`
- Check if user has proper permissions

### OpenLiteSpeed Not Serving Laravel
- Ensure document root points to `public` directory
- Restart OpenLiteSpeed: `/usr/local/lsws/bin/lswsctrl restart`
- Check vHost configuration in CyberPanel

---

## Support

For issues, check the logs:
- Laravel: `storage/logs/laravel.log`
- OpenLiteSpeed: `/usr/local/lsws/logs/error.log`
- Supervisor: `/var/log/supervisor/supervisord.log`
