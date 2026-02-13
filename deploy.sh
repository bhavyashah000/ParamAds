#!/bin/bash
# ============================================================================
# ParamAds Deployment Script
# ============================================================================
set -e

echo "🚀 ParamAds Deployment Script"
echo "=============================="

# Check prerequisites
command -v docker >/dev/null 2>&1 || { echo "Docker is required but not installed."; exit 1; }
command -v docker-compose >/dev/null 2>&1 || command -v docker compose >/dev/null 2>&1 || { echo "Docker Compose is required."; exit 1; }

# Determine compose command
if command -v docker-compose >/dev/null 2>&1; then
    COMPOSE="docker-compose"
else
    COMPOSE="docker compose"
fi

# Load environment
if [ ! -f .env ]; then
    echo "Creating .env from .env.example..."
    cp backend/.env.example .env
    echo "⚠️  Please update .env with your configuration before continuing."
    exit 1
fi

# Parse command
case "${1}" in
    setup)
        echo "📦 Setting up ParamAds for the first time..."

        # Build containers
        $COMPOSE build --no-cache

        # Start services
        $COMPOSE up -d mysql redis
        echo "Waiting for MySQL to be ready..."
        sleep 15

        # Start all services
        $COMPOSE up -d

        # Run migrations
        echo "Running database migrations..."
        $COMPOSE exec backend php artisan migrate --force

        # Generate app key if not set
        $COMPOSE exec backend php artisan key:generate --force

        # Create storage link
        $COMPOSE exec backend php artisan storage:link

        # Seed initial data
        $COMPOSE exec backend php artisan db:seed --force

        # Optimize
        $COMPOSE exec backend php artisan config:cache
        $COMPOSE exec backend php artisan route:cache
        $COMPOSE exec backend php artisan view:cache

        echo "✅ ParamAds setup complete!"
        echo "   Access: http://localhost"
        echo "   API:    http://localhost/api"
        ;;

    start)
        echo "▶️  Starting ParamAds..."
        $COMPOSE up -d
        echo "✅ ParamAds is running."
        ;;

    stop)
        echo "⏹️  Stopping ParamAds..."
        $COMPOSE down
        echo "✅ ParamAds stopped."
        ;;

    restart)
        echo "🔄 Restarting ParamAds..."
        $COMPOSE restart
        echo "✅ ParamAds restarted."
        ;;

    update)
        echo "📥 Updating ParamAds..."
        git pull origin main

        # Rebuild containers
        $COMPOSE build

        # Run migrations
        $COMPOSE exec backend php artisan migrate --force

        # Clear caches
        $COMPOSE exec backend php artisan config:cache
        $COMPOSE exec backend php artisan route:cache
        $COMPOSE exec backend php artisan view:cache

        # Restart
        $COMPOSE up -d

        echo "✅ ParamAds updated."
        ;;

    migrate)
        echo "🗄️  Running migrations..."
        $COMPOSE exec backend php artisan migrate --force
        echo "✅ Migrations complete."
        ;;

    logs)
        $COMPOSE logs -f ${2:-backend}
        ;;

    status)
        $COMPOSE ps
        ;;

    backup)
        echo "💾 Creating database backup..."
        TIMESTAMP=$(date +%Y%m%d_%H%M%S)
        $COMPOSE exec mysql mysqldump -u paramads -p${DB_PASSWORD:-secret} paramads > "backups/paramads_${TIMESTAMP}.sql"
        echo "✅ Backup saved to backups/paramads_${TIMESTAMP}.sql"
        ;;

    *)
        echo "Usage: $0 {setup|start|stop|restart|update|migrate|logs|status|backup}"
        echo ""
        echo "Commands:"
        echo "  setup    - First-time setup (build, migrate, seed)"
        echo "  start    - Start all services"
        echo "  stop     - Stop all services"
        echo "  restart  - Restart all services"
        echo "  update   - Pull latest code and update"
        echo "  migrate  - Run database migrations"
        echo "  logs     - View logs (optionally specify service)"
        echo "  status   - Show service status"
        echo "  backup   - Create database backup"
        exit 1
        ;;
esac
