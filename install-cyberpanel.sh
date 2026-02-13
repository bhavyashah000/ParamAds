#!/bin/bash
#=============================================================================
# ParamAds - CyberPanel Installation Script
# This script installs ParamAds directly into CyberPanel document root
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
echo "║    ParamAds - CyberPanel Installation            ║"
echo "╚═══════════════════════════════════════════════════╝"
echo -e "${NC}"

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}Please run as root or with sudo${NC}"
    exit 1
fi

# Get domain from current directory
DOMAIN=$(basename "$(pwd)")
DOC_ROOT="$(pwd)/public_html"

echo -e "${YELLOW}Installation Details:${NC}"
echo -e "  Domain: ${GREEN}${DOMAIN}${NC}"
echo -e "  Document Root: ${GREEN}${DOC_ROOT}${NC}"
echo ""

# Check if public_html exists
if [ ! -d "$DOC_ROOT" ]; then
    echo -e "${RED}Error: ${DOC_ROOT} does not exist!${NC}"
    echo -e "Please create the website in CyberPanel first."
    exit 1
fi

echo -e "${YELLOW}Step 1: Checking prerequisites...${NC}"

# Check PHP version
PHP_VERSION=$(php -v 2>/dev/null | head -n 1 | cut -d' ' -f2 | cut -d'.' -f1,2)
echo -e "  PHP Version: ${GREEN}${PHP_VERSION}${NC}"

# Check required PHP extensions
REQUIRED_EXTS=(pdo_mysql mbstring xml curl zip gd bcmath json openssl)
for ext in "${REQUIRED_EXTS[@]}"; do
    if php -m | grep -qi "$ext"; then
        echo -e "  PHP ext-${ext}: ${GREEN}✓${NC}"
    else
        echo -e "  PHP ext-${ext}: ${YELLOW}⚠ Missing${NC}"
    fi
done

# Check Composer
if ! command -v composer &> /dev/null; then
    echo -e "${YELLOW}  Installing Composer...${NC}"
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi
echo -e "  Composer: ${GREEN}✓${NC}"

echo -e "\n${YELLOW}Step 2: Downloading ParamAds...${NC}"

# Download latest ParamAds from GitHub
TEMP_DIR="/tmp/paramads_install_$$"
mkdir -p "$TEMP_DIR"
cd "$TEMP_DIR"

echo -e "  Cloning ParamAds repository..."
git clone --depth 1 https://github.com/bhavyashah000/ParamAds.git . 2>/dev/null || {
    echo -e "${RED}Failed to clone repository. Trying alternative method...${NC}"
    # Fallback: Download as ZIP
    curl -L https://github.com/bhavyashah000/ParamAds/archive/refs/heads/main.zip -o paramads.zip
    unzip -q paramads.zip
    mv ParamAds-main/* .
    rm -rf ParamAds-main paramads.zip
}

echo -e "  ${GREEN}✓ Repository downloaded${NC}"

echo -e "\n${YELLOW}Step 3: Installing application files...${NC}"

# Copy backend files to document root
echo -e "  Copying files to ${DOC_ROOT}..."
cp -r backend/* "$DOC_ROOT/"
cp backend/.env.example "$DOC_ROOT/.env"

cd "$DOC_ROOT"

echo -e "  ${GREEN}✓ Files copied${NC}"

echo -e "\n${YELLOW}Step 4: Configuring environment...${NC}"

# Generate a random APP_KEY
APP_KEY=$(php -r 'echo "base64:" . base64_encode(random_bytes(32));')

# Update .env file
sed -i "s|APP_NAME=.*|APP_NAME=ParamAds|" .env
sed -i "s|APP_URL=.*|APP_URL=https://${DOMAIN}|" .env
sed -i "s|APP_KEY=.*|APP_KEY=${APP_KEY}|" .env
sed -i "s|APP_ENV=.*|APP_ENV=production|" .env
sed -i "s|APP_DEBUG=.*|APP_DEBUG=false|" .env

# Database configuration (using localhost)
sed -i "s|DB_HOST=.*|DB_HOST=127.0.0.1|" .env
sed -i "s|DB_DATABASE=.*|DB_DATABASE=paramads_${DOMAIN//./}_db|" .env
sed -i "s|DB_USERNAME=.*|DB_USERNAME=paramads_user|" .env
sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=$(openssl rand -base64 16)|" .env

echo -e "  ${GREEN}✓ Environment configured${NC}"

echo -e "\n${YELLOW}Step 5: Installing dependencies...${NC}"

composer install --no-dev --optimize-autoloader 2>&1 | tail -5

echo -e "  ${GREEN}✓ Dependencies installed${NC}"

echo -e "\n${YELLOW}Step 6: Setting permissions...${NC}"

# Set proper ownership and permissions
chown -R nobody:nobody "$DOC_ROOT"
chmod -R 755 "$DOC_ROOT"
chmod -R 775 "$DOC_ROOT/storage"
chmod -R 775 "$DOC_ROOT/bootstrap/cache"

echo -e "  ${GREEN}✓ Permissions set${NC}"

echo -e "\n${YELLOW}Step 7: Creating .htaccess for routing...${NC}"

# Create .htaccess for Laravel routing
cat > "$DOC_ROOT/public/.htaccess" << 'HTACCESS'
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

echo -e "  ${GREEN}✓ .htaccess created${NC}"

echo -e "\n${YELLOW}Step 8: Setting up cron job for Laravel scheduler...${NC}"

# Add Laravel scheduler cron
(crontab -l 2>/dev/null | grep -v "ParamAds"; echo "* * * * * cd ${DOC_ROOT} && php artisan schedule:run >> /dev/null 2>&1 # ParamAds") | crontab -

echo -e "  ${GREEN}✓ Cron job added${NC}"

echo -e "\n${YELLOW}Step 9: Cleaning up temporary files...${NC}"

rm -rf "$TEMP_DIR"

echo -e "\n${GREEN}╔═══════════════════════════════════════════════════╗"
echo "║         Installation Complete!                     ║"
echo "╚═══════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "  ${CYAN}Application URL:${NC}   https://${DOMAIN}"
echo -e "  ${CYAN}Installer URL:${NC}     https://${DOMAIN}/install"
echo -e "  ${CYAN}Document Root:${NC}     ${DOC_ROOT}/public"
echo ""
echo -e "  ${YELLOW}IMPORTANT NEXT STEPS:${NC}"
echo -e "  1. Visit https://${DOMAIN}/install to run the web installer"
echo -e "  2. The installer will set up the database and create your admin account"
echo -e "  3. Configure your API keys in the admin dashboard"
echo ""
echo -e "  ${YELLOW}Database Info:${NC}"
DB_NAME=$(grep "DB_DATABASE=" "$DOC_ROOT/.env" | cut -d'=' -f2)
DB_USER=$(grep "DB_USERNAME=" "$DOC_ROOT/.env" | cut -d'=' -f2)
echo -e "  Database Name: ${GREEN}${DB_NAME}${NC}"
echo -e "  Database User: ${GREEN}${DB_USER}${NC}"
echo -e "  (Password is in .env file)"
echo ""
echo -e "  ${YELLOW}If you encounter issues:${NC}"
echo -e "  - Check .env file configuration"
echo -e "  - Verify database credentials"
echo -e "  - Check Laravel logs: tail -f ${DOC_ROOT}/storage/logs/laravel.log"
echo ""
