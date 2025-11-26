#!/bin/bash

# ====================================================================
# FinanSphere - Quick Setup Script
# ====================================================================
# Script ini membantu setup awal aplikasi dengan Redis integration
# ====================================================================

echo "╔══════════════════════════════════════════════════════════╗"
echo "║         FinanSphere - Quick Setup Script               ║"
echo "╚══════════════════════════════════════════════════════════╝"
echo ""

# Check if .env exists
if [ ! -f .env ]; then
    echo "📋 Creating .env file from .env.example..."
    cp .env.example .env
    echo "✅ .env file created!"
    echo ""
else
    echo "✅ .env file already exists!"
    echo ""
fi

# Generate application key if not set
if ! grep -q "APP_KEY=base64:" .env; then
    echo "🔑 Generating application key..."
    php artisan key:generate
    echo "✅ Application key generated!"
    echo ""
fi

# Install Composer dependencies
echo "📦 Installing Composer dependencies..."
composer install --optimize-autoloader
echo "✅ Composer dependencies installed!"
echo ""

# Install NPM dependencies
echo "📦 Installing NPM dependencies..."
npm install
echo "✅ NPM dependencies installed!"
echo ""

# Create database
echo "🗄️  Creating database..."
php artisan migrate --force
echo "✅ Database created!"
echo ""

# Create storage link
echo "🔗 Creating storage link..."
php artisan storage:link
echo "✅ Storage link created!"
echo ""

# Check Redis
echo "🔍 Checking Redis connection..."
if command -v redis-cli &> /dev/null; then
    if redis-cli ping &> /dev/null; then
        echo "✅ Redis is running!"
    else
        echo "⚠️  Redis is not running. Starting Redis..."
        if [[ "$OSTYPE" == "darwin"* ]]; then
            brew services start redis
        elif [[ "$OSTYPE" == "linux-gnu"* ]]; then
            sudo systemctl start redis-server
        fi
    fi
else
    echo "⚠️  Redis is not installed!"
    echo "   Please install Redis:"
    echo "   macOS: brew install redis && brew services start redis"
    echo "   Linux: sudo apt install redis-server && sudo systemctl start redis-server"
fi
echo ""

# Clear and optimize caches
echo "⚡ Optimizing application..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
echo "✅ Application optimized!"
echo ""

# Create admin user (optional)
echo "👤 Would you like to create an admin user? (y/n)"
read -r create_user
if [[ $create_user == "y" || $create_user == "Y" ]]; then
    php artisan make:filament-user
fi
echo ""

echo "╔══════════════════════════════════════════════════════════╗"
echo "║              Setup Complete! 🎉                         ║"
echo "╚══════════════════════════════════════════════════════════╝"
echo ""
echo "📚 Next Steps:"
echo "   1. Configure your .env file (database, Redis, etc.)"
echo "   2. Start the development server: php artisan serve"
echo "   3. Start Vite dev server: npm run dev"
echo "   4. Access admin: http://localhost:8000/secure-management-panel-xyz123"
echo ""
echo "📖 Documentation:"
echo "   - Quick Start: QUICK_START.md"
echo "   - Full Guide: OPTIMIZATION_GUIDE.md"
echo "   - Changelog: CHANGELOG.md"
echo ""
echo "💡 Useful Commands:"
echo "   - composer optimize     : Optimize for production"
echo "   - composer clear        : Clear all caches"
echo "   - composer check-status : Check Redis status"
echo ""
echo "✨ Happy coding!"
