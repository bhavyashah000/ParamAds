#!/bin/bash
#=============================================================================
# ParamAds - CyberPanel Deployment Script
# This script automates deployment on CyberPanel hosting
#=============================================================================

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

echo -e "${CYAN}"
echo "╔═══════════════════════════════════════════════════╗"
echo "║       ParamAds - CyberPanel Deployment           ║"
echo "╚═══════════════════════════════════════════════════╝"
echo -e "${NC}"

# Check if running as root or with sudo
if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}Please run as root or with sudo${NC}"
    exit 1
fi

# Prompt for domain
read -p "Enter your domain (e.g., paramads.yourdomain.com): " DOMAIN
read -p "Enter MySQL database name: " DB_NAME
read -p "Enter MySQL database user: " DB_USER
read -sp "Enter MySQL database password: " DB_PASS
echo ""
read -p "Enter the website document root path (e.g., /home/${DOMAIN}/public_html): " DOC_ROOT

echo -e "\n${YELLOW}Step 1: Checking prerequisites...${NC}"

# Check PHP version
PHP_VERSION=$(php -v 2>/dev/null | head -n 1 | cut -d' ' -f2 | cut -d'.' -f1,2)
echo -e "  PHP Version: ${GREEN}${PHP_VERSION}${NC}"

# Check required PHP extensions
REQUIRED_EXTS=(pdo_mysql mbstring xml curl zip gd bcmath json openssl tokenizer)
for ext in "${REQUIRED_EXTS[@]}"; do
    if php -m | grep -qi "$ext"; then
        echo -e "  PHP ext-${ext}: ${GREEN}OK${NC}"
    else
        echo -e "  PHP ext-${ext}: ${RED}MISSING${NC} - Installing..."
        apt-get install -y php${PHP_VERSION}-${ext} 2>/dev/null || yum install -y php-${ext} 2>/dev/null || true
    fi
done

# Check Composer
if ! command -v composer &> /dev/null; then
    echo -e "${YELLOW}  Installing Composer...${NC}"
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi
echo -e "  Composer: ${GREEN}OK${NC}"

echo -e "\n${YELLOW}Step 2: Setting up application files...${NC}"

# Copy backend files to document root
if [ -d "${DOC_ROOT}" ]; then
    echo -e "  Copying files to ${DOC_ROOT}..."
    cp -r backend/* "${DOC_ROOT}/"
    cp backend/.env.example "${DOC_ROOT}/.env"
else
    echo -e "${RED}  Document root ${DOC_ROOT} does not exist!${NC}"
    echo -e "  Create the website in CyberPanel first, then re-run this script."
    exit 1
fi

cd "${DOC_ROOT}"

echo -e "\n${YELLOW}Step 3: Configuring environment...${NC}"

# Update .env file
sed -i "s|APP_URL=.*|APP_URL=https://${DOMAIN}|" .env
sed -i "s|DB_DATABASE=.*|DB_DATABASE=${DB_NAME}|" .env
sed -i "s|DB_USERNAME=.*|DB_USERNAME=${DB_USER}|" .env
sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=${DB_PASS}|" .env
sed -i "s|DB_HOST=.*|DB_HOST=127.0.0.1|" .env
sed -i "s|APP_ENV=.*|APP_ENV=production|" .env
sed -i "s|APP_DEBUG=.*|APP_DEBUG=false|" .env

# Generate app key
php artisan key:generate --force

echo -e "\n${YELLOW}Step 4: Installing dependencies...${NC}"
composer install --no-dev --optimize-autoloader

echo -e "\n${YELLOW}Step 5: Setting permissions...${NC}"
chown -R nobody:nobody "${DOC_ROOT}"
chmod -R 755 "${DOC_ROOT}"
chmod -R 775 "${DOC_ROOT}/storage"
chmod -R 775 "${DOC_ROOT}/bootstrap/cache"

echo -e "\n${YELLOW}Step 6: Creating OpenLiteSpeed rewrite rules...${NC}"

# Create .htaccess for OpenLiteSpeed (CyberPanel default)
cat > "${DOC_ROOT}/public/.htaccess" << 'HTACCESS'
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
HTACCESS

echo -e "\n${YELLOW}Step 7: Configuring CyberPanel vHost...${NC}"

# Create vHost configuration for OpenLiteSpeed
VHOST_CONF="/usr/local/lsws/conf/vhosts/${DOMAIN}/vhost.conf"
if [ -f "${VHOST_CONF}" ]; then
    echo -e "  Updating vHost configuration..."

    # Backup original
    cp "${VHOST_CONF}" "${VHOST_CONF}.bak"

    # Update document root to point to public directory
    cat > "${VHOST_CONF}" << VHOST
docRoot                   ${DOC_ROOT}/public
vhDomain                  ${DOMAIN}
enableGzip                1
enableIpGeo               1

index  {
  useServer               0
  indexFiles               index.php, index.html
}

context / {
  location                ${DOC_ROOT}/public
  allowBrowse             1
  rewrite  {
    enable                1
    autoLoadHtaccess      1
  }
}

rewrite  {
  enable                  1
  autoLoadHtaccess        1
  rules                   <<<END_rules
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^ index.php [L]
END_rules
}

phpIniOverride  {
  php_value upload_max_filesize 50M
  php_value post_max_size 50M
  php_value max_execution_time 300
  php_value memory_limit 256M
}
VHOST

    echo -e "  ${GREEN}vHost configuration updated${NC}"
else
    echo -e "  ${YELLOW}vHost config not found at ${VHOST_CONF}${NC}"
    echo -e "  Please manually set document root to: ${DOC_ROOT}/public"
fi

echo -e "\n${YELLOW}Step 8: Setting up cron job for Laravel scheduler...${NC}"

# Add Laravel scheduler cron
(crontab -l 2>/dev/null; echo "* * * * * cd ${DOC_ROOT} && php artisan schedule:run >> /dev/null 2>&1") | crontab -

echo -e "\n${YELLOW}Step 9: Setting up queue worker (Supervisor)...${NC}"

# Install supervisor if not present
apt-get install -y supervisor 2>/dev/null || yum install -y supervisor 2>/dev/null || true

cat > /etc/supervisor/conf.d/paramads-worker.conf << SUPERVISOR
[program:paramads-worker]
process_name=%(program_name)s_%(process_num)02d
command=php ${DOC_ROOT}/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=nobody
numprocs=2
redirect_stderr=true
stdout_logfile=${DOC_ROOT}/storage/logs/worker.log
stopwaitsecs=3600
SUPERVISOR

supervisorctl reread
supervisorctl update
supervisorctl start paramads-worker:*

echo -e "\n${YELLOW}Step 10: Restarting OpenLiteSpeed...${NC}"
/usr/local/lsws/bin/lswsctrl restart

echo -e "\n${GREEN}╔═══════════════════════════════════════════════════╗"
echo "║           Deployment Complete!                     ║"
echo "╚═══════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "  ${CYAN}Application URL:${NC} https://${DOMAIN}"
echo -e "  ${CYAN}Installer URL:${NC}   https://${DOMAIN}/install"
echo -e "  ${CYAN}Document Root:${NC}   ${DOC_ROOT}/public"
echo ""
echo -e "  ${YELLOW}IMPORTANT NEXT STEPS:${NC}"
echo -e "  1. Visit https://${DOMAIN}/install to run the web installer"
echo -e "  2. The installer will set up the database and create your admin account"
echo -e "  3. Configure your API keys in the admin dashboard"
echo ""
echo -e "  ${YELLOW}If using SSL:${NC}"
echo -e "  - Issue SSL certificate from CyberPanel > SSL > Manage SSL"
echo -e "  - Or use: certbot --webroot -w ${DOC_ROOT}/public -d ${DOMAIN}"
echo ""
