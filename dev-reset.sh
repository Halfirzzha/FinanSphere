#!/bin/bash

# ====================================================================
# FinanSphere - Development Reset Script
# ====================================================================
# This script clears all caches for development environment
# ====================================================================

echo "🔄 Resetting FinanSphere Development Environment..."
echo ""

# Clear all caches
echo "🧹 Clearing all caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear
echo "✅ All caches cleared!"
echo ""

# Clear Redis cache
echo "🧹 Clearing Redis cache..."
php artisan cache:forget '*'
echo "✅ Redis cache cleared!"
echo ""

# Regenerate key (optional, commented out for safety)
# echo "🔑 Regenerating application key..."
# php artisan key:generate
# echo "✅ Key regenerated!"
# echo ""

echo "✨ Development environment reset complete!"
echo ""
echo "💡 Tips:"
echo "   - Start dev server: php artisan serve"
echo "   - Watch assets: npm run dev"
echo "   - Monitor Redis: redis-cli monitor"
echo ""
