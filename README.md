# QR Eventos Grupo México

## 🎯 Descripción del Proyecto

SaaS para gestión integral de eventos corporativos con códigos QR, control de asistencia en tiempo real y sistema de rifas automatizado.

## 📋 Funcionalidades Solicitadas

### ✅ Implementado
- Sistema multi-usuario (SaaS)
- Base de datos completa con relaciones
- Modelos Eloquent con métodos especializados
- Servicio de generación de códigos QR
- Sistema de autenticación (Laravel Breeze)

### 🔄 En Desarrollo
- Importación masiva desde CSV
- Sistema de envío de emails
- Frontend con React + Inertia
- APIs para tiempo real

### ⏳ Pendiente
- Escáner QR para control de asistencia
- Dashboard en tiempo real
- Sistema de rifas por categorías
- Estadísticas avanzadas

## 🛠️ Tecnologías

- **Backend**: Laravel 12.31.1, PHP 8.4
- **Frontend**: React, Inertia.js, Tailwind CSS
- **Base de Datos**: MySQL
- **QR Codes**: endroid/qr-code
- **CSV**: maatwebsite/excel

## 📊 Estructura de Datos

### Eventos
Cada usuario puede crear múltiples eventos con:
- Información básica (nombre, fecha, ubicación)
- Lista de invitados importada desde CSV
- Premios con stock y categorías
- Control de asistencia en tiempo real

### Invitados
Cada invitado tiene:
- Datos personales (nombre completo, número empleado, área)
- Código QR único con información encriptada
- Categorías de premios a los que puede acceder
- Estado de asistencia

### Sistema de Rifas
- Premios organizados por categorías
- Solo invitados con asistencia confirmada pueden participar
- Control automático de stock
- Sorteos en tiempo real

## 🚀 Instalación

```bash
# Clonar repositorio
git clone [url-repositorio]
cd QREventosGrupoMexico

# Instalar dependencias PHP
composer install

# Instalar dependencias Node.js
npm install

# Crear directorios de cache de bootstrap
mkdir -p bootstrap/cache
chmod -R 775 bootstrap/cache

# Darle permisos al directorio storage
chmod -R 775 storage

# Configurar ambiente
cp .env.example .env
php artisan key:generate

# Ejecutar migraciones
php artisan migrate

# Compilar assets
npm run dev

# Iniciar servidor
php artisan serve
```

## 📈 Estado Actual

**Fase 1**: Fundación ✅ (Completado)
- Base de datos diseñada e implementada
- Modelos con relaciones completas
- Servicios base configurados

**Fase 2**: Desarrollo Core 🔄 (En progreso)
- Controladores principales
- Frontend React
- Sistema de importación CSV

**Fase 3**: Funcionalidades Avanzadas ⏳ (Planeado)
- Tiempo real con WebSockets
- Sistema de emails masivos
- Estadísticas avanzadas

## 📝 Próximos Pasos

1. **Controladores**: Implementar lógica CRUD para eventos, invitados y premios
2. **Frontend**: Crear interfaces React para gestión de eventos
3. **CSV Import**: Sistema de importación masiva con validaciones
4. **QR Scanner**: Implementar escáner en tiempo real
5. **Email System**: Configurar envío automático de códigos QR

---

Para más detalles sobre el progreso del proyecto, consulta [PROJECT_STATUS.md](PROJECT_STATUS.md)