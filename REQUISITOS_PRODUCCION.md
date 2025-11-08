# 🚀 Requisitos de Servidor de Producción
## QR Eventos

---

## 📋 Requisitos del Sistema Operativo

### **Sistema Operativo Recomendado**
- ✅ **Ubuntu Server 22.04 LTS** o superior
- ✅ **Debian 11+**
- ✅ **CentOS 8+** / **Rocky Linux 8+**

---

## 🔧 Software Base Requerido

### **1. Servidor Web**
Elegir UNA de las siguientes opciones:

#### Opción A: Nginx (Recomendado) ⭐
```bash
# Versión mínima requerida
nginx >= 1.18
```
**Configuraciones necesarias:**
- PHP-FPM configurado
- Reglas de reescritura para Laravel
- SSL/TLS habilitado (Let's Encrypt)
- Compresión gzip/brotli

#### Opción B: Apache
```bash
# Versión mínima requerida
apache2 >= 2.4

# Módulos requeridos:
- mod_rewrite
- mod_ssl
- mod_headers
- mod_proxy_fcgi (para PHP-FPM)
```

---

### **2. PHP** ⚡
```bash
# Versión requerida
PHP >= 8.1 (Recomendado: PHP 8.2 o 8.3)
```

#### **Extensiones PHP Obligatorias:**
```bash
php8.2-cli           # Interfaz de línea de comandos
php8.2-fpm           # FastCGI Process Manager
php8.2-mysql         # Soporte MySQL/MariaDB
php8.2-mbstring      # Manejo de strings multibyte
php8.2-xml           # Procesamiento XML
php8.2-curl          # Cliente HTTP
php8.2-zip           # Compresión ZIP
php8.2-gd            # Procesamiento de imágenes (QR codes)
php8.2-intl          # Internacionalización
php8.2-bcmath        # Matemáticas de precisión arbitraria
php8.2-tokenizer     # Tokenización PHP
php8.2-fileinfo      # Información de archivos
php8.2-dom           # Manipulación DOM
php8.2-imagick       # Alternativa a GD (opcional pero recomendado)
```

#### **Configuración PHP (php.ini):**
```ini
memory_limit = 256M
upload_max_filesize = 50M
post_max_size = 50M
max_execution_time = 300
max_input_time = 300
file_uploads = On
allow_url_fopen = On

# Producción
display_errors = Off
log_errors = On
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT

# OPcache (Obligatorio para rendimiento)
opcache.enable = 1
opcache.memory_consumption = 256
opcache.interned_strings_buffer = 16
opcache.max_accelerated_files = 10000
opcache.validate_timestamps = 0 # Producción
opcache.revalidate_freq = 0
opcache.fast_shutdown = 1
```

---

### **3. Base de Datos** 🗄️

#### **MySQL** (Recomendado) ⭐
```bash
# Versión mínima
MySQL >= 8.0

# O alternativamente
MariaDB >= 10.6
```

#### **Configuración Requerida:**
```sql
# Crear base de datos
CREATE DATABASE qr_eventos_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Crear usuario
CREATE USER 'qr_eventos_user'@'localhost' IDENTIFIED BY 'contraseña_segura';
GRANT ALL PRIVILEGES ON qr_eventos_db.* TO 'qr_eventos_user'@'localhost';
FLUSH PRIVILEGES;
```

#### **my.cnf - Optimizaciones:**
```ini
[mysqld]
max_connections = 200
innodb_buffer_pool_size = 1G # 70-80% de RAM disponible
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
query_cache_size = 0 # Deshabilitado en MySQL 8+
```

---

### **4. Composer** 📦
```bash
# Última versión estable
composer >= 2.5
```

**Instalación global recomendada:**
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

---

### **5. Node.js y NPM** 🟢
```bash
# Versión LTS recomendada
Node.js >= 18.x LTS (Recomendado: 20.x LTS)
NPM >= 9.x
```

**Instalación vía nvm (recomendado):**
```bash
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.0/install.sh | bash
nvm install 20
nvm use 20
```

---

### **6. Sistema de Colas** 📬

#### **Supervisor** (Para Laravel Queue Workers)
```bash
# Requerido para procesar jobs en segundo plano
supervisor >= 4.2
```

**Configuración necesaria:**
```ini
[program:qr-eventos-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /ruta/al/proyecto/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/ruta/al/proyecto/storage/logs/worker.log
stopwaitsecs=3600
```

---

### **7. Redis** (Opcional pero muy recomendado) 🔴
```bash
# Para caché, sesiones y colas
Redis >= 6.2
```

**Extensión PHP:**
```bash
php8.2-redis
```

---

## 📚 Dependencias del Proyecto

### **Backend - Composer (PHP)**

#### **Dependencias de Producción:**
```json
{
  "php": "^8.1",
  "laravel/framework": "^10.10",
  "inertiajs/inertia-laravel": "^1.3.3",
  "laravel/sanctum": "^3.2",
  "laravel/tinker": "^2.8",
  "endroid/qr-code": "^5.0",          // Generación de códigos QR
  "maatwebsite/excel": "^3.1",         // Importación/Exportación CSV
  "guzzlehttp/guzzle": "^7.2",        // Cliente HTTP
  "tightenco/ziggy": "*"              // Rutas Laravel en JavaScript
}
```

#### **Instalación:**
```bash
composer install --optimize-autoloader --no-dev
```

---

### **Frontend - NPM (Node.js)**

#### **Dependencias de Producción:**
```json
{
  "react": "^18.2.0",
  "react-dom": "^18.2.0",
  "@inertiajs/react": "^2.0.0",
  "@headlessui/react": "^2.2.8",
  "@heroicons/react": "^2.2.0",
  "axios": "^1.11.0",
  "react-hot-toast": "^2.6.0",
  "lucide-react": "^0.544.0",
  "clsx": "^2.1.1",
  "tailwind-merge": "^3.3.1"
}
```

#### **Dependencias de Desarrollo:**
```json
{
  "vite": "^7.0.4",
  "@vitejs/plugin-react": "^4.2.0",
  "laravel-vite-plugin": "^2.0.0",
  "tailwindcss": "^3.2.1",
  "@tailwindcss/forms": "^0.5.3",
  "@tailwindcss/vite": "^4.0.0",
  "autoprefixer": "^10.4.12",
  "postcss": "^8.4.31"
}
```

#### **Compilación para Producción:**
```bash
npm install
npm run build
```

---

## 🔐 Requisitos de Seguridad

### **1. SSL/TLS**
- ✅ Certificado SSL válido (Let's Encrypt gratis)
- ✅ HTTPS obligatorio
- ✅ HTTP → HTTPS redirect

### **2. Firewall**
```bash
# Puertos necesarios
22   - SSH (solo IPs específicas)
80   - HTTP (redirect a HTTPS)
443  - HTTPS
3306 - MySQL (solo localhost o red privada)
```

### **3. Permisos de Archivos**
```bash
# Directorio del proyecto
chown -R www-data:www-data /ruta/proyecto
find /ruta/proyecto -type f -exec chmod 644 {} \;
find /ruta/proyecto -type d -exec chmod 755 {} \;

# Permisos especiales
chmod -R 775 storage bootstrap/cache
chmod 600 .env
```

---

## 📊 Requisitos de Hardware

### **Mínimo (Hasta 100 usuarios concurrentes):**
- **CPU:** 2 cores
- **RAM:** 4 GB
- **Disco:** 20 GB SSD
- **Ancho de banda:** 100 Mbps

### **Recomendado (100-500 usuarios concurrentes):**
- **CPU:** 4 cores
- **RAM:** 8 GB
- **Disco:** 50 GB SSD
- **Ancho de banda:** 1 Gbps

### **Producción Alta (500+ usuarios concurrentes):**
- **CPU:** 8+ cores
- **RAM:** 16+ GB
- **Disco:** 100+ GB SSD NVMe
- **Ancho de banda:** 1+ Gbps
- **Balanceador de carga:** Recomendado

---

### **Configuración .env:**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.ejemplo.com
MAIL_PORT=587
MAIL_USERNAME=tu_usuario
MAIL_PASSWORD=tu_contraseña
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=eventos@grupomexico.com
MAIL_FROM_NAME="QR Eventos Grupo México"
```

---

## 🔄 Sistema de Colas

**Obligatorio** para:
- ✉️ Envío de emails
- 📊 Procesamiento de imports CSV
- 🎲 Sistema de rifas
- 📈 Generación de reportes

### **Configuración .env:**
```env
QUEUE_CONNECTION=database  # O redis (mejor rendimiento)
```

### **Supervisor configurado** para mantener workers activos

---

## 🛠️ Herramientas Adicionales Opcionales

### **Monitoreo y Logs:**
- **Laravel Telescope** (desarrollo/staging)
- **Sentry** (errores en producción)
- **New Relic** / **Datadog** (APM)
- **Logrotate** (rotación de logs)

### **Backup:**
- **Backups automáticos** de base de datos
- **Backups de archivos** (códigos QR, uploads)
- Recomendado: diario con retención de 30 días

### **CDN (Opcional):**
- **CloudFlare** (gratis, muy recomendado)
- **AWS CloudFront**
- Para assets estáticos y archivos QR

---

## 📝 Checklist de Instalación

```bash
# 1. Sistema Operativo
□ Ubuntu Server 22.04 instalado y actualizado

# 2. Software Base
□ Nginx o Apache instalado y configurado
□ PHP 8.2+ con todas las extensiones
□ MySQL 8.0+ instalado
□ Composer instalado globalmente
□ Node.js 20 LTS instalado
□ Supervisor instalado
□ Redis instalado (opcional)

# 3. Seguridad
□ Firewall configurado
□ SSL certificado instalado
□ Permisos de archivos correctos
□ Usuario dedicado para la aplicación

# 4. Configuración Laravel
□ Variables de entorno (.env) configuradas
□ Base de datos creada
□ Migraciones ejecutadas
□ Assets compilados (npm run build)
□ Queue workers configurados

# 5. Pruebas
□ Aplicación accesible vía HTTPS
□ Base de datos conectada
□ Emails enviándose correctamente
□ Colas procesándose
□ QR codes generándose
□ Import CSV funcionando

# 6. Monitoreo
□ Logs configurados
□ Backups automáticos
□ Monitoreo de uptime
```

---

## 🚀 Comandos de Despliegue

```bash
# 1. Copiar archivos desde el zip
Copiar archivos desde el zip
cd QREventosGrupoMexico

# 2. Instalar dependencias
composer install --optimize-autoloader --no-dev
npm ci
npm run build

# 3. Configurar entorno
cp .env.example .env
php artisan key:generate

# 4. Base de datos
php artisan migrate --force

# 5. Optimizaciones de producción
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 6. Permisos
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data .

# 7. Reiniciar servicios
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start qr-eventos-worker:*
```

---

## 📞 Soporte y Documentación

Para más información sobre:
- **Laravel**: https://laravel.com/docs/10.x
- **React + Inertia**: https://inertiajs.com/
- **Tailwind CSS**: https://tailwindcss.com/
- **QR Code Library**: https://github.com/endroid/qr-code

---

**Última actualización:** 5 de noviembre de 2025
