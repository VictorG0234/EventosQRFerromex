# 📧 Sistema de Emails - QR Eventos

## Descripción General

El Sistema de Emails de QR Eventos proporciona una solución completa para la gestión automática de comunicaciones por correo electrónico en eventos corporativos. Incluye plantillas profesionales, envíos masivos, recordatorios automáticos y confirmaciones de asistencia.

## 🚀 Características Principales

### 1. **Emails Automáticos**
- ✅ **Bienvenida**: Envío automático al registrar un invitado con email
- ✅ **Confirmación de Asistencia**: Envío automático al escanear QR o registro manual
- ✅ **Recordatorios Programados**: 24h y 2h antes del evento
- ✅ **Resumen del Evento**: Para el organizador al finalizar

### 2. **Plantillas Profesionales**
- 🎨 **6 Plantillas HTML Responsivas**:
  - Bienvenida con código QR
  - Recordatorio del evento
  - Confirmación de asistencia
  - Resumen del evento
  - Mensaje personalizado
  - Notificación de ganador de rifa

### 3. **Gestión desde la Interfaz Web**
- 📊 Dashboard de estadísticas de email
- 📨 Envíos masivos con un clic
- ✍️ Mensajes personalizados
- 👀 Vista previa de plantillas
- 📈 Métricas de cobertura de email

## 🛠️ Configuración del Sistema

### 1. **Configuración de Email (.env)**
```env
# Para desarrollo - emails se guardan en logs
MAIL_MAILER=log

# Para producción - configurar SMTP
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tu_email@gmail.com
MAIL_PASSWORD=tu_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=eventos@tuempresa.com
MAIL_FROM_NAME="QR Eventos"
```

### 2. **Configuración de Colas**
```env
# Usar base de datos para colas
QUEUE_CONNECTION=database
```

### 3. **Migrar Tablas de Jobs**
```bash
php artisan migrate
```

### 4. **Ejecutar Worker de Colas (Producción)**
```bash
php artisan queue:work --daemon
```

## 📋 Uso del Sistema

### 1. **Acceso a la Interfaz**
- Navegar a cualquier evento
- Clic en el botón "📧 Emails" en el header
- Dashboard completo con estadísticas

### 2. **Envíos Automáticos**
Los emails se envían automáticamente en estos casos:
- **Al agregar invitado**: Email de bienvenida (si tiene email)
- **Al escanear QR**: Confirmación de asistencia
- **Al registro manual**: Confirmación de asistencia

### 3. **Envíos Manuales**
Desde el dashboard de emails:
- **Bienvenida Masiva**: A todos los invitados con email
- **Recordatorios**: 24h o 2h antes del evento
- **Mensajes Personalizados**: Con asunto y contenido custom
- **Resumen del Evento**: Para el organizador

### 4. **Recordatorios Automáticos**
Comando para programar en cron:
```bash
# Cada hora, verificar eventos que necesitan recordatorios
0 * * * * php artisan emails:send-reminders
```

## 🎨 Plantillas de Email

### 1. **Email de Bienvenida**
- **Cuándo**: Al registrar invitado con email
- **Contenido**: Información del evento + código QR personal
- **Características**: Diseño responsive, colores corporativos

### 2. **Recordatorio del Evento**
- **Cuándo**: 24h o 2h antes del evento
- **Contenido**: Countdown, información del evento, código QR
- **Características**: Urgencia visual según proximidad

### 3. **Confirmación de Asistencia**
- **Cuándo**: Al registrar asistencia
- **Contenido**: Confirmación exitosa, hora de llegada
- **Características**: Diseño de éxito, información del evento

### 4. **Resumen del Evento**
- **Cuándo**: Manual por el organizador
- **Contenido**: Estadísticas completas, análisis de asistencia
- **Características**: Gráficos, insights, recomendaciones

### 5. **Mensaje Personalizado**
- **Cuándo**: Manual desde la interfaz
- **Contenido**: Asunto y mensaje custom del organizador
- **Características**: Flexible, con información del evento

### 6. **Ganador de Rifa**
- **Cuándo**: Al ejecutar sorteos (futuro)
- **Contenido**: Notificación de premio, instrucciones
- **Características**: Diseño celebratorio, animaciones CSS

## 🧪 Testing y Debugging

### 1. **Comando de Testing**
```bash
# Test completo del sistema
php artisan emails:test

# Test de evento específico
php artisan emails:test 1

# Test de plantilla específica
php artisan emails:test 1 --template=welcome
```

### 2. **Verificar Plantillas**
```bash
# Verificar que todas las plantillas existen
php artisan emails:validate-templates
```

### 3. **Monitoreo de Colas**
```bash
# Ver jobs pendientes
php artisan queue:work --verbose

# Ver jobs fallidos
php artisan queue:failed

# Reintentar jobs fallidos
php artisan queue:retry all
```

### 4. **Logs de Email**
```bash
# En modo log, ver emails en:
tail -f storage/logs/laravel.log | grep "mail"
```

## 📊 Métricas y Estadísticas

### 1. **Dashboard de Emails**
- Total de invitados
- Invitados con email vs sin email
- Porcentaje de cobertura
- Historial de envíos

### 2. **APIs de Estadísticas**
```javascript
// Obtener estadísticas via API
fetch(`/events/${eventId}/emails/statistics`)
```

## ⚙️ Configuración Avanzada

### 1. **Personalizar Plantillas**
Las plantillas están en: `resources/views/emails/`
- `guest-welcome.blade.php`
- `event-reminder.blade.php`
- `attendance-confirmation.blade.php`
- `event-summary.blade.php`
- `custom-message.blade.php`
- `raffle-winner.blade.php`

### 2. **Personalizar Servicios**
- `EmailService`: Lógica de envío
- `SendEmailJob`: Jobs en background
- `EmailController`: API endpoints

### 3. **Configurar Queue Workers**
Para producción, usar supervisor:
```ini
[program:qreventos-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/qreventos/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
```

## 🔍 Troubleshooting

### 1. **Emails No se Envían**
- Verificar configuración SMTP en .env
- Verificar que queue worker esté ejecutándose
- Revisar logs en `storage/logs/laravel.log`

### 2. **Plantillas No se Ven Bien**
- Verificar que todas las plantillas existen
- Comprobar rutas de assets
- Verificar CSS inline para compatibilidad

### 3. **Jobs Se Quedan Pendientes**
- Ejecutar `php artisan queue:work`
- Verificar tabla `jobs` en la base de datos
- Revisar jobs fallidos

### 4. **Problemas de Rendimiento**
- Usar Redis en lugar de database para colas
- Configurar múltiples workers
- Implementar rate limiting

## 📈 Futuras Mejoras

### Versión 2.0 (Planificado)
- 📧 Templates más avanzados
- 📊 Analytics detallados de apertura
- 🔄 Campañas de email automatizadas
- 📱 Notificaciones push
- 🎯 Segmentación avanzada de invitados
- 📋 A/B testing de plantillas

---

**Nota**: Este sistema está completamente integrado con el resto de QR Eventos y funciona automáticamente sin intervención manual, pero permite control total desde la interfaz web.