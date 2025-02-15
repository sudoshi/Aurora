#!/bin/bash

# Navigate to the project directory
cd "$(dirname "$0")"

# Optionally clear caches
php artisan optimize:clear

echo "Please manually stop the Laravel development server in its terminal."
echo "Please manually stop the Vite development server in its terminal."
echo "Please manually stop the WebSocket server in its terminal."
