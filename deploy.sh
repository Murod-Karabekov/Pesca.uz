#!/bin/bash
set -e

# ============================================================
# Pesca.uz — VDS Production Deployment Script
# Server: 78.24.220.99 (FirstVDS)
# Domain: pesca.uz
# ============================================================

DOMAIN="pesca.uz"
PROJECT_DIR="/opt/pesca"
REPO_URL="https://github.com/Murod-Karabekov/Pesca.uz.git"
EMAIL="admin@pesca.uz"  # Change to your real email for Let's Encrypt

echo "========================================="
echo "  Pesca.uz Production Deploy"
echo "========================================="

# ── 1. System update & Docker install ──
echo ""
echo "📦 [1/7] Tizimni yangilash va Docker o'rnatish..."
apt-get update -y && apt-get upgrade -y
apt-get install -y curl git ca-certificates gnupg lsb-release

# Install Docker if not present
if ! command -v docker &> /dev/null; then
    echo "🐳 Docker o'rnatilmoqda..."
    curl -fsSL https://get.docker.com | sh
    systemctl enable docker
    systemctl start docker
fi

# Install Docker Compose plugin if not present
if ! docker compose version &> /dev/null; then
    echo "🐳 Docker Compose o'rnatilmoqda..."
    apt-get install -y docker-compose-plugin
fi

echo "✅ Docker versiyasi: $(docker --version)"
echo "✅ Docker Compose: $(docker compose version)"

# ── 2. Clone / Pull repository ──
echo ""
echo "📥 [2/7] Repozitoriyni yuklash..."
if [ -d "$PROJECT_DIR" ]; then
    cd "$PROJECT_DIR"
    git pull origin master
else
    git clone "$REPO_URL" "$PROJECT_DIR"
    cd "$PROJECT_DIR"
fi

# ── 3. Create production environment file ──
echo ""
echo "⚙️  [3/7] Production muhit sozlamalari..."
if [ ! -f "$PROJECT_DIR/.env.prod.local" ]; then
    # Generate random passwords
    APP_SECRET=$(openssl rand -hex 16)
    MYSQL_ROOT_PASS=$(openssl rand -base64 16 | tr -dc 'a-zA-Z0-9' | head -c 20)
    MYSQL_PASS=$(openssl rand -base64 16 | tr -dc 'a-zA-Z0-9' | head -c 20)

    cat > "$PROJECT_DIR/.env.prod.local" << ENVEOF
# ── Symfony ──
APP_ENV=prod
APP_SECRET=${APP_SECRET}
DEFAULT_URI=https://${DOMAIN}

# ── Database ──
DATABASE_URL=mysql://pesca:${MYSQL_PASS}@database:3306/pesca?serverVersion=8.0&charset=utf8mb4
MYSQL_ROOT_PASSWORD=${MYSQL_ROOT_PASS}
MYSQL_DATABASE=pesca
MYSQL_USER=pesca
MYSQL_PASSWORD=${MYSQL_PASS}

# ── Messenger ──
MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0

# ── Mailer ──
MAILER_DSN=null://null
ENVEOF

    echo "✅ .env.prod.local yaratildi (parollar avtomatik generatsiya qilindi)"
    echo "   MySQL root paroli: ${MYSQL_ROOT_PASS}"
    echo "   MySQL pesca paroli: ${MYSQL_PASS}"
    echo ""
    echo "⚠️  BU PAROLLARNI SAQLANG! Keyinroq ko'ra olmaysiz."
else
    echo "✅ .env.prod.local allaqachon mavjud — o'tkazib yuborildi"
fi

# ── 4. Firewall setup ──
echo ""
echo "🔒 [4/7] Firewall sozlash..."
if command -v ufw &> /dev/null; then
    ufw allow 22/tcp   # SSH
    ufw allow 80/tcp   # HTTP
    ufw allow 443/tcp  # HTTPS
    ufw --force enable
    echo "✅ UFW: 22, 80, 443 portlar ochiq"
else
    echo "⚠️  UFW topilmadi — iptables yoki boshqa firewall tekshiring"
fi

# ── 5. Build and start containers ──
echo ""
echo "🏗️  [5/7] Docker konteynerlarni build qilish va ishga tushirish..."
cd "$PROJECT_DIR"
docker compose -f docker-compose.prod.yml down --remove-orphans 2>/dev/null || true
docker compose -f docker-compose.prod.yml build --no-cache
docker compose -f docker-compose.prod.yml up -d

echo "⏳ Konteynerlar ishga tushishini kutish (30 sek)..."
sleep 30

# Check containers
echo ""
echo "📋 Konteyner holatlari:"
docker compose -f docker-compose.prod.yml ps

# ── 6. Obtain SSL certificate ──
echo ""
echo "🔐 [6/7] SSL sertifikat olish (Let's Encrypt)..."
echo ""
echo "⚠️  MUHIM: Avval DNS A-record sozlangan bo'lishi kerak!"
echo "   ${DOMAIN} → 78.24.220.99"
echo "   www.${DOMAIN} → 78.24.220.99"
echo ""
read -p "DNS tayyor bo'lsa Enter bosing (yoki 'skip' yozing, keyinroq o'rnatish uchun): " dns_ready

if [ "$dns_ready" != "skip" ]; then
    # Get SSL cert
    docker compose -f docker-compose.prod.yml run --rm certbot certonly \
        --webroot \
        --webroot-path=/var/www/certbot \
        --email "$EMAIL" \
        --agree-tos \
        --no-eff-email \
        -d "$DOMAIN" \
        -d "www.$DOMAIN"

    if [ $? -eq 0 ]; then
        echo "✅ SSL sertifikat muvaffaqiyatli olindi!"

        # Switch to SSL nginx config
        echo "🔄 Nginx SSL konfiguratsiyaga o'tkazilmoqda..."
        cp "$PROJECT_DIR/docker/nginx/prod-ssl.conf" "$PROJECT_DIR/docker/nginx/prod.conf"

        # Restart nginx
        docker compose -f docker-compose.prod.yml restart nginx
        echo "✅ HTTPS yoqildi! https://${DOMAIN}"
    else
        echo "❌ SSL sertifikat olishda xatolik. DNS tekshiring va qayta urinib ko'ring:"
        echo "   docker compose -f docker-compose.prod.yml run --rm certbot certonly --webroot --webroot-path=/var/www/certbot --email $EMAIL --agree-tos --no-eff-email -d $DOMAIN -d www.$DOMAIN"
    fi
else
    echo "⏭️  SSL o'tkazib yuborildi. Keyinroq qo'lda o'rnatish:"
    echo "   cd $PROJECT_DIR"
    echo "   docker compose -f docker-compose.prod.yml run --rm certbot certonly --webroot --webroot-path=/var/www/certbot --email $EMAIL --agree-tos --no-eff-email -d $DOMAIN -d www.$DOMAIN"
    echo "   cp docker/nginx/prod-ssl.conf docker/nginx/prod.conf"
    echo "   docker compose -f docker-compose.prod.yml restart nginx"
fi

# ── 7. SSL auto-renew cron ──
echo ""
echo "⏰ [7/7] SSL avtomatik yangilash cron..."
CRON_JOB="0 3 * * * cd $PROJECT_DIR && docker compose -f docker-compose.prod.yml run --rm certbot renew --quiet && docker compose -f docker-compose.prod.yml restart nginx"
(crontab -l 2>/dev/null | grep -v "certbot renew"; echo "$CRON_JOB") | crontab -
echo "✅ Cron qo'shildi: har kuni 03:00 da SSL yangilanadi"

# ── Done! ──
echo ""
echo "========================================="
echo "  ✅ Deploy tugadi!"
echo "========================================="
echo ""
echo "  🌐 Sayt:  http://${DOMAIN} (yoki https://${DOMAIN} SSL bilan)"
echo "  📁 Papka: ${PROJECT_DIR}"
echo ""
echo "  Foydali buyruqlar:"
echo "    docker compose -f docker-compose.prod.yml logs -f        # Loglarni ko'rish"
echo "    docker compose -f docker-compose.prod.yml restart         # Qayta ishga tushirish"
echo "    docker compose -f docker-compose.prod.yml down            # To'xtatish"
echo "    docker compose -f docker-compose.prod.yml exec php bash   # PHP konteynerga kirish"
echo ""
