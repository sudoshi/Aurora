#!/bin/bash

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    local color=$1
    local message=$2
    echo -e "${color}${message}${NC}"
}

# Store the script's directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PID_DIR="${SCRIPT_DIR}/storage/pids"
LARAVEL_PID_FILE="${PID_DIR}/laravel.pid"
VITE_PID_FILE="${PID_DIR}/vite.pid"

# Function to kill process and its children
kill_process_tree() {
    local pid=$1
    if [ -n "$pid" ]; then
        # Get all child processes
        children=$(pgrep -P $pid)

        # Kill children first
        for child in $children; do
            kill_process_tree $child
        done

        # Kill the parent process
        kill -9 $pid 2>/dev/null
    fi
}

# Stop Laravel and Vite processes
if [ -f "${LARAVEL_PID_FILE}" ]; then
  PID=$(cat "${LARAVEL_PID_FILE}")
  print_status "$YELLOW" "Stopping Laravel server (PID: ${PID})..."
  kill_process_tree "${PID}"
  rm "${LARAVEL_PID_FILE}"
fi

if [ -f "${VITE_PID_FILE}" ]; then
    PID=$(cat "${VITE_PID_FILE}")
    print_status "$YELLOW" "Stopping Vite server (PID: ${PID})..."
    kill_process_tree "${PID}"
    rm "${VITE_PID_FILE}"
fi

# Clean up any remaining processes on the ports
print_status "$YELLOW" "Cleaning up remaining processes on port 8000..."
lsof -ti:8000 | xargs kill -9 2>/dev/null

print_status "$YELLOW" "Cleaning up remaining processes on port 5173..."
lsof -ti:5173 | xargs kill -9 2>/dev/null

# Clear Laravel caches
print_status "$YELLOW" "Clearing Laravel caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

print_status "$GREEN" "Development servers stopped successfully!"
