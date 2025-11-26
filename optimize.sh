#!/bin/bash

# ====================================================================
# FinanSphere - Production Optimization Script
# ====================================================================
# This script optimizes the Laravel application for production use
# by clearing and caching configurations, routes, and views.
# ====================================================================

echo "🚀 Starting FinanSphere Optimization..."
echo ""

# Clear all caches first
echo "🧹 Clearing all caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear
echo "✅ Caches cleared!"
echo ""

# Optimize configurations
echo "⚡ Caching configurations..."
php artisan config:cache
echo "✅ Config cached!"
echo ""

# Optimize routes
echo "⚡ Caching routes..."
php artisan route:cache
echo "✅ Routes cached!"
echo ""

# Optimize views
echo "⚡ Caching views..."
php artisan view:cache
echo "✅ Views cached!"
echo ""

# Optimize Filament
echo "⚡ Optimizing Filament..."
php artisan filament:optimize
echo "✅ Filament optimized!"
echo ""

# Generate autoload files
echo "⚡ Optimizing autoload..."
composer dump-autoload --optimize
echo "✅ Autoload optimized!"
echo ""

# Check Redis connection
echo "🔍 Testing Redis connection..."
if php artisan tinker --execute="Cache::driver('redis')->get('test');" 2>/dev/null; then
    echo "✅ Redis connection successful!"
else
    echo "⚠️  Warning: Redis connection failed. Check your Redis server."
fi
echo ""

echo "✨ Optimization complete!"
echo ""
echo "📋 Next steps:"
echo "   1. Make sure Redis is running: redis-cli ping"
echo "   2. Start queue worker: php artisan queue:work redis --daemon"
echo "   3. Monitor logs: tail -f storage/logs/laravel.log"
echo ""
echo "🎉 Your application is now optimized for production!"
