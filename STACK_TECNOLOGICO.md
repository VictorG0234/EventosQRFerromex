# 📋 Resumen Rápido - Stack Tecnológico
## QR Eventos Grupo México

---

## 🎯 Stack Principal

### **Backend**
- **Framework:** Laravel 10.x
- **Lenguaje:** PHP 8.1+ (Recomendado: 8.2 o 8.3)
- **Base de Datos:** MySQL 8.0+ / MariaDB 10.6+
- **Autenticación:** Laravel Sanctum + Breeze

### **Frontend**
- **Framework UI:** React 18.2
- **Framework Full-Stack:** Inertia.js 2.0
- **CSS Framework:** Tailwind CSS 3.x
- **Build Tool:** Vite 7.x
- **Componentes:** Headless UI, Heroicons, Lucide React

### **Infraestructura**
- **Servidor Web:** Nginx (recomendado) o Apache
- **Process Manager:** Supervisor (para colas)
- **Cache/Sesiones:** Redis (opcional pero recomendado)
- **Sistema de Colas:** Database (o Redis)

---

## 📦 Dependencias Clave del Proyecto

### **Generación de QR Codes**
```json
"endroid/qr-code": "^5.0"
```

### **Importación/Exportación CSV**
```json
"maatwebsite/excel": "^3.1"
```

### **Cliente HTTP**
```json
"guzzlehttp/guzzle": "^7.2"
```

### **Rutas en JavaScript**
```json
"tightenco/ziggy": "*"
```

---

## 🔧 Extensiones PHP Requeridas

```bash
php-cli
php-fpm
php-mysql
php-mbstring
php-xml
php-curl
php-zip
php-gd          # Para QR codes
php-intl
php-bcmath
php-tokenizer
php-fileinfo
php-dom
php-redis       # Opcional
```

---

## 🚀 Software a Instalar en Producción

### **Esenciales:**
1. ✅ **PHP 8.1+** con extensiones listadas arriba
2. ✅ **Nginx** o Apache
3. ✅ **MySQL 8.0+** o MariaDB 10.6+
4. ✅ **Composer 2.5+**
5. ✅ **Node.js 18+ LTS** (para compilar assets)
6. ✅ **Supervisor** (para queue workers)

### **Recomendados:**
7. 🟡 **Redis 6.2+** (cache y colas)
8. 🟡 **Certbot** (SSL gratis con Let's Encrypt)
9. 🟡 **Fail2ban** (seguridad)
10. 🟡 **Git** (despliegue)

---

## 🖥️ Hardware Mínimo

### **Producción Pequeña (< 100 usuarios):**
- CPU: 2 cores
- RAM: 4 GB
- Disco: 20 GB SSD
- Ancho de banda: 100 Mbps

### **Producción Media (100-500 usuarios):**
- CPU: 4 cores
- RAM: 8 GB
- Disco: 50 GB SSD
- Ancho de banda: 1 Gbps

---

## 📧 Servicios de Email (SMTP)

Elegir uno:
- **AWS SES** (económico, 62k emails gratis/mes desde EC2)
- **SendGrid** (100 emails/día gratis)
- **Mailgun** (5k emails/mes gratis)
- **Mailtrap** (solo para staging/testing)

---

## 🔐 Seguridad Básica

```bash
# Puertos a abrir en firewall
22   - SSH (solo IPs específicas)
80   - HTTP (redirect a HTTPS)
443  - HTTPS
3306 - MySQL (solo localhost)
6379 - Redis (solo localhost)
```

```bash
# Permisos de archivos
storage/          → 775
bootstrap/cache/  → 775
.env              → 600
otros archivos    → 644
directorios       → 755
```

---

## 📊 Variables de Entorno Críticas

```env
# Aplicación
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...generada...

# Base de Datos
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=qr_eventos_db
DB_USERNAME=qr_user
DB_PASSWORD=contraseña_segura

# Colas
QUEUE_CONNECTION=database  # o redis

# Email
MAIL_MAILER=smtp
MAIL_HOST=smtp.ejemplo.com
MAIL_PORT=587
MAIL_USERNAME=tu_usuario
MAIL_PASSWORD=tu_contraseña

# Cache/Sesiones
CACHE_STORE=redis        # o database
SESSION_DRIVER=redis     # o database
```

---

## ⚡ Comandos de Instalación Rápida

### **Ubuntu/Debian:**
```bash
# Actualizar sistema
sudo apt update && sudo apt upgrade -y

# Instalar PHP 8.2 y extensiones
sudo apt install -y php8.2 php8.2-fpm php8.2-mysql php8.2-mbstring \
  php8.2-xml php8.2-curl php8.2-zip php8.2-gd php8.2-intl \
  php8.2-bcmath php8.2-redis php8.2-imagick

# Instalar Nginx
sudo apt install -y nginx

# Instalar MySQL
sudo apt install -y mysql-server

# Instalar Redis
sudo apt install -y redis-server

# Instalar Supervisor
sudo apt install -y supervisor

# Instalar Composer
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer

# Instalar Node.js (vía nvm)
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.0/install.sh | bash
source ~/.bashrc
nvm install 20
nvm use 20
```

---

## 🔄 Workflow de Despliegue

```bash
# 1. Descargar código
git pull origin main

# 2. Dependencias
composer install --optimize-autoloader --no-dev
npm ci && npm run build

# 3. Migraciones
php artisan migrate --force

# 4. Optimizaciones
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Reiniciar servicios
sudo systemctl restart php8.2-fpm nginx
sudo supervisorctl restart qr-eventos-worker:*
```

---

## 📚 Documentación Completa

Para documentación detallada, ver: **[REQUISITOS_PRODUCCION.md](./REQUISITOS_PRODUCCION.md)**

---

**Stack Version:** Laravel 10 + React 18 + Inertia.js 2  
**Última actualización:** 5 de noviembre de 2025
