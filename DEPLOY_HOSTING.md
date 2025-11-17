# Guía de Despliegue en Hosting Web

## Problemas Comunes y Soluciones

### 1. Extensiones PHP Faltantes

El error que estás viendo indica que faltan extensiones PHP en el hosting. Las extensiones requeridas son:

- `ext-gd` (para procesamiento de imágenes)
- `ext-zip` (para manejo de archivos ZIP/Excel)

#### Solución A: Contactar al hosting
Solicita al proveedor de hosting que habilite estas extensiones en tu cuenta:
- PHP GD Extension
- PHP Zip Extension

#### Solución B: Ignorar requisitos de plataforma (temporal)
Si no puedes instalar las extensiones, puedes desplegar usando:

```bash
composer install --ignore-platform-reqs --optimize-autoloader --no-dev
```

**⚠️ ADVERTENCIA:** Esto instalará las dependencias pero algunas funciones (importación de Excel, generación de QR) podrían no funcionar correctamente.

### 2. Permisos de Cámara en HTTPS

Los navegadores modernos **requieren HTTPS** para acceder a la cámara. En hosting web, asegúrate de:

1. **Tener certificado SSL/TLS instalado** (muchos hostings ofrecen Let's Encrypt gratis)
2. **Forzar HTTPS** en tu `.htaccess`:

```apache
# Forzar HTTPS
RewriteCond %{HTTPS} !=on
RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### 3. Estructura de Archivos en Hosting

La mayoría de hostings compartidos esperan que los archivos estén en `public_html` o `www`:

```
/home/usuario/
├── public_html/          ← Debe apuntar a tu carpeta 'public'
│   ├── index.php
│   ├── .htaccess
│   └── build/
└── laravel/             ← Resto de archivos Laravel (FUERA de public_html)
    ├── app/
    ├── bootstrap/
    ├── config/
    ├── database/
    ├── resources/
    ├── routes/
    ├── storage/
    ├── vendor/
    ├── .env
    └── artisan
```

#### Pasos de Instalación:

1. **Subir archivos:**
   - Contenido de carpeta `public/` → `public_html/`
   - Todo lo demás → Carpeta fuera de `public_html/` (ej: `laravel/`)

2. **Editar `public_html/index.php`:**
   ```php
   require __DIR__.'/../laravel/vendor/autoload.php';
   $app = require_once __DIR__.'/../laravel/bootstrap/app.php';
   ```

3. **Configurar permisos:**
   ```bash
   chmod -R 755 storage bootstrap/cache
   chmod -R 775 storage/logs storage/framework
   ```

### 4. Variables de Entorno (.env)

Asegúrate de que tu archivo `.env` esté configurado correctamente:

```env
APP_NAME="Eventos QR Ferromex"
APP_ENV=production
APP_KEY=base64:... # Generar con: php artisan key:generate
APP_DEBUG=false    # ¡IMPORTANTE: false en producción!
APP_URL=https://tudominio.com

DB_CONNECTION=mysql
DB_HOST=localhost  # O el que proporcione tu hosting
DB_PORT=3306
DB_DATABASE=nombre_base_datos
DB_USERNAME=usuario_bd
DB_PASSWORD=contraseña_bd

SESSION_DRIVER=database  # Recomendado para shared hosting
QUEUE_CONNECTION=database
```

### 5. Optimización para Producción

Después de subir los archivos, ejecuta (vía SSH o terminal del hosting):

```bash
# Limpiar cachés
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Optimizar para producción
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Migrar base de datos
php artisan migrate --force

# Crear link de storage
php artisan storage:link
```

### 6. Solución Temporal para Cámara

Si no puedes obtener HTTPS inmediatamente, puedes:

**Opción A:** Usar el registro manual
- El sistema ya tiene un método de registro manual de asistencia
- Ve a la página del escáner y usa "Ingresar Manualmente"

**Opción B:** Usar ngrok localmente
```bash
# En tu máquina local
brew install ngrok  # macOS
ngrok http 8000

# Esto te dará una URL HTTPS temporal
# https://abc123.ngrok.io
```

### 7. Archivo .htaccess Actualizado

El `.htaccess` en `public/` ya está configurado con:
- Reescritura de URLs
- Seguridad básica
- Compresión
- Cache de archivos estáticos

### 8. Troubleshooting Común

#### Error 500
```bash
# Ver logs
tail -f storage/logs/laravel.log

# O verificar logs del servidor
# En cPanel: Métricas → Errores
```

#### Páginas en blanco
```bash
# Verificar permisos
chmod -R 755 storage bootstrap/cache

# Limpiar cachés
php artisan cache:clear
```

#### Assets no cargan (CSS/JS)
- Verifica que `APP_URL` en `.env` sea correcta
- Ejecuta: `npm run build` antes de subir
- Verifica que la carpeta `public/build/` exista

### 9. Checklist Pre-Deploy

- [ ] Extensiones PHP habilitadas (gd, zip, pdo, mbstring, openssl)
- [ ] PHP >= 8.2
- [ ] Certificado SSL instalado (para cámara)
- [ ] Base de datos creada
- [ ] `.env` configurado correctamente
- [ ] `APP_DEBUG=false` en producción
- [ ] Permisos de storage configurados
- [ ] Archivos compilados (`npm run build`)
- [ ] `composer install --optimize-autoloader --no-dev`
- [ ] Migraciones ejecutadas

### 10. Comando Rápido de Deploy

Crea este script `deploy.sh` para automatizar:

```bash
#!/bin/bash

echo "🚀 Iniciando deploy..."

# Optimizar dependencias
composer install --optimize-autoloader --no-dev --ignore-platform-reqs

# Compilar assets
npm run build

# Limpiar cachés
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Optimizar
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Migraciones
php artisan migrate --force

echo "✅ Deploy completado!"
```

Ejecútalo con: `chmod +x deploy.sh && ./deploy.sh`

## Soporte

Si sigues teniendo problemas:
1. Revisa los logs: `storage/logs/laravel.log`
2. Contacta a tu proveedor de hosting para habilitar extensiones PHP
3. Verifica que HTTPS esté funcionando correctamente
