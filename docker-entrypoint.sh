#!/bin/bash
set -e

# Entry point for Laravel on Ubuntu 24.04.3 LTS
# Prints a banner + system info (appears in stdout logs on Render)
# Then performs basic Laravel startup tasks and launches supervisord.

# Create log directories
mkdir -p /var/log/supervisor /var/log/nginx /var/log/laravel

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
MAGENTA='\033[0;35m'
CYAN='\033[0;36m'
WHITE='\033[0;37m'
BOLD='\033[1m'
NC='\033[0m' # No Color

# Optionally show banner (default: true)
if [ "${SHOW_BANNER:-true}" = "true" ]; then
  clear
  echo -e "${CYAN}"
  cat << 'EOF'
    ╔═══════════════════════════════════════════════════════════╗
    ║                                                           ║
    ║   ███████╗███╗   ███╗ █████╗ ██████╗ ████████╗          ║
    ║   ██╔════╝████╗ ████║██╔══██╗██╔══██╗╚══██╔══╝          ║
    ║   ███████╗██╔████╔██║███████║██████╔╝   ██║             ║
    ║   ╚════██║██║╚██╔╝██║██╔══██║██╔══██╗   ██║             ║
    ║   ███████║██║ ╚═╝ ██║██║  ██║██║  ██║   ██║             ║
    ║   ╚══════╝╚═╝     ╚═╝╚═╝  ╚═╝╚═╝  ╚═╝   ╚═╝             ║
    ║                                                           ║
    ║   ████████╗██╗ ██████╗██╗  ██╗███████╗████████╗         ║
    ║   ╚══██╔══╝██║██╔════╝██║ ██╔╝██╔════╝╚══██╔══╝         ║
    ║      ██║   ██║██║     █████╔╝ █████╗     ██║            ║
    ║      ██║   ██║██║     ██╔═██╗ ██╔══╝     ██║            ║
    ║      ██║   ██║╚██████╗██║  ██╗███████╗   ██║            ║
    ║      ╚═╝   ╚═╝ ╚═════╝╚═╝  ╚═╝╚══════╝   ╚═╝            ║
    ║                                                           ║
    ║                  Smart Ticket (Laravel)                   ║
    ╚═══════════════════════════════════════════════════════════╝
EOF
  echo -e "${NC}"

  # System info
  HOSTNAME=$(hostname)
  OS_NAME=$(awk -F= '/^PRETTY_NAME/{gsub(/\"/,"",$2);print $2}' /etc/os-release)
  KERNEL=$(uname -r)
  UPTIME=$(uptime -p | sed 's/up //')
  NODE_VERSION="not-installed"
  NPM_VERSION="not-installed"
  if command -v node >/dev/null 2>&1; then
    NODE_VERSION=$(node --version 2>/dev/null || echo "unknown")
  fi
  if command -v npm >/dev/null 2>&1; then
    NPM_VERSION=$(npm --version 2>/dev/null || echo "unknown")
  fi

  # CPU info (cgroups compatible)
  if [ -f /sys/fs/cgroup/cpu/cpu.cfs_quota_us ]; then
    CPU_QUOTA=$(cat /sys/fs/cgroup/cpu/cpu.cfs_quota_us)
    CPU_PERIOD=$(cat /sys/fs/cgroup/cpu/cpu.cfs_period_us)
    if [ "$CPU_QUOTA" != "-1" ]; then
      CPU_ALLOCATED=$(echo "scale=2; $CPU_QUOTA / $CPU_PERIOD" | bc)
    else
      CPU_ALLOCATED=$(nproc)
    fi
  elif [ -f /sys/fs/cgroup/cpu.max ]; then
    CPU_INFO=$(cat /sys/fs/cgroup/cpu.max)
    if [ "$CPU_INFO" != "max 100000" ]; then
      CPU_QUOTA=$(echo $CPU_INFO | awk '{print $1}')
      CPU_PERIOD=$(echo $CPU_INFO | awk '{print $2}')
      CPU_ALLOCATED=$(echo "scale=2; $CPU_QUOTA / $CPU_PERIOD" | bc)
    else
      CPU_ALLOCATED=$(nproc)
    fi
  else
    CPU_ALLOCATED=$(nproc)
  fi

  # Memory info
  if [ -f /sys/fs/cgroup/memory/memory.limit_in_bytes ]; then
    MEM_LIMIT=$(cat /sys/fs/cgroup/memory/memory.limit_in_bytes)
    MEM_USAGE=$(cat /sys/fs/cgroup/memory/memory.usage_in_bytes)
    if [ "$MEM_LIMIT" != "9223372036854771712" ]; then
      MEM_LIMIT_MB=$(echo "scale=0; $MEM_LIMIT / 1024 / 1024" | bc)
      MEM_USAGE_MB=$(echo "scale=0; $MEM_USAGE / 1024 / 1024" | bc)
      MEM_INFO="${MEM_USAGE_MB}MiB / ${MEM_LIMIT_MB}MiB"
    else
      MEM_INFO=$(free -h | awk '/^Mem:/ {printf "%s / %s", $3, $2}')
    fi
  elif [ -f /sys/fs/cgroup/memory.max ]; then
    MEM_LIMIT=$(cat /sys/fs/cgroup/memory.max)
    MEM_USAGE=$(cat /sys/fs/cgroup/memory.current)
    if [ "$MEM_LIMIT" != "max" ]; then
      MEM_LIMIT_MB=$(echo "scale=0; $MEM_LIMIT / 1024 / 1024" | bc)
      MEM_USAGE_MB=$(echo "scale=0; $MEM_USAGE / 1024 / 1024" | bc)
      MEM_INFO="${MEM_USAGE_MB}MiB / ${MEM_LIMIT_MB}MiB"
    else
      MEM_INFO=$(free -h | awk '/^Mem:/ {printf "%s / %s", $3, $2}')
    fi
  else
    MEM_INFO=$(free -h | awk '/^Mem:/ {printf "%s / %s", $3, $2}')
  fi

  DISK_INFO=$(df -h / | awk 'NR==2 {printf "%s / %s (%s)", $3, $2, $5}')

  # App version from composer.json if present
  if [ -f /var/www/html/composer.json ]; then
    APP_VERSION=$(grep '"version"' /var/www/html/composer.json | head -1 | awk -F'"' '{print $4}' || true)
  else
    APP_VERSION="unknown"
  fi

  echo -e "${BOLD}${YELLOW}smart-ticket@${HOSTNAME}${NC}"
  echo -e "${BOLD}${YELLOW}$(printf '%.0s-' {1..50})${NC}"
  echo -e "${BOLD}${BLUE}OS:${NC}           ${OS_NAME}"
  echo -e "${BOLD}${BLUE}Kernel:${NC}       ${KERNEL}"
  echo -e "${BOLD}${BLUE}Uptime:${NC}       ${UPTIME}"
  echo -e "${BOLD}${BLUE}Shell:${NC}        bash $(bash --version 2>/dev/null | head -1 | awk '{print $4}' || echo 'unknown')"
  echo -e "${BOLD}${MAGENTA}Node.js:${NC}      ${NODE_VERSION}"
  echo -e "${BOLD}${MAGENTA}NPM:${NC}          ${NPM_VERSION}"
  echo -e "${BOLD}${MAGENTA}Framework:${NC}    Laravel + PHP 8.2"
  echo -e "${BOLD}${GREEN}CPU:${NC}          ${CPU_ALLOCATED} vCPU"
  echo -e "${BOLD}${GREEN}Memory:${NC}       ${MEM_INFO}"
  echo -e "${BOLD}${GREEN}Disk (/):${NC}     ${DISK_INFO}"
  echo -e "${BOLD}${CYAN}App Version:${NC}  ${APP_VERSION}"
  echo -e "${BOLD}${CYAN}Environment:${NC}  ${APP_ENV:-production}"
  echo ""
  echo -e "${BOLD}${GREEN}✅ Starting services...${NC}"
  echo ""
fi

# Ensure correct ownership for Laravel
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/log/laravel || true
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache || true

cd /var/www/html || true

# Install composer autoload files if vendor missing or dump-autoload otherwise
if [ -f composer.json ]; then
  if [ ! -d vendor ]; then
    echo "[Entrypoint] Installing Composer dependencies (production)..."
    composer install --no-dev --optimize-autoloader --no-interaction || true
  else
    composer dump-autoload --optimize || true
  fi
fi

# Generate APP_KEY if not set
if [ -z "${APP_KEY:-}" ] && [ -f artisan ]; then
  echo "[Entrypoint] Generating APP_KEY"
  php artisan key:generate --force || true
fi

# Run migrations if DB env is present
if [ -n "${DB_HOST:-}" ] && [ -f artisan ]; then
  echo "[Entrypoint] Running migrations (if available)"
  php artisan migrate --force || echo "[Entrypoint] Migrate returned non-zero, continuing"
fi

echo "[Entrypoint] Clearing and caching configs"
if [ -f artisan ]; then
  php artisan config:cache || true
  php artisan route:cache || true
fi

echo "[Entrypoint] Starting supervisord"
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
