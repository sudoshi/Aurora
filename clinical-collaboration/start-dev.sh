#!/bin/bash

# Navigate to the project directory
cd "$(dirname "$0")"

# Clear caches
php artisan optimize:clear

echo "To start the Laravel development server, run in a new terminal:"
echo "php artisan serve --host=127.0.0.1 --port=8000"

echo "To start the Vite development server, run in a new terminal:"
echo "npm run dev"

echo "To start the WebSocket server, run in a new terminal:"
echo "php artisan websockets:serve"
