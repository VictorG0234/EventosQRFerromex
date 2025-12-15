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

### Comando Principal de Testing

El proyecto incluye un comando completo para probar el sistema de rifas:

```bash
php artisan test:single-raffle
```

### Opciones del Comando

```bash
# Con archivos personalizados
php artisan test:single-raffle \
  --guests-file=storage/app/exports/guests.csv \
  --attendances-file=storage/app/exports/asistencias.txt \
  --prizes-file=storage/app/exports/premios.csv \
  --general-winners=76

# Con seed fijo para tests determinísticos
php artisan test:single-raffle --seed=12345
```

### Archivos Requeridos

El comando necesita los siguientes archivos (por defecto en `storage/app/exports/`):

1. **guests.csv**: Archivo CSV con la lista de invitados
2. **asistencias.txt**: Archivo de texto con números de empleado (uno por línea)
3. **premios.csv**: Archivo CSV con la lista de premios

### Qué Hace el Test

El comando ejecuta un test completo que:

1. ✅ Crea un evento de prueba
2. ✅ Importa invitados desde CSV
3. ✅ Crea invitados manualmente
4. ✅ Marca asistencias desde archivo
5. ✅ Genera códigos QR para todos los invitados
6. ✅ Crea premios manualmente y desde CSV
7. ✅ Ejecuta rifa pública para todos los premios
8. ✅ Ejecuta rifa general con el número especificado de ganadores
9. ✅ Valida todas las reglas de negocio:
   - Descripciones prohibidas en ganadores
   - Categorías prohibidas
   - No hay ganadores repetidos
   - Exactamente 1 ganador IMEX en rifa pública
   - Exactamente 2 ganadores IMEX en rifa general
   - Ganadores de rifa pública no participan en rifa general
   - Invitados INV no participan en ninguna rifa
   - Stock se actualiza correctamente
10. ✅ Exporta resultados a CSV en `storage/app/exports/`

### Resultados

Al finalizar, el comando muestra:
- ✅ Resumen de todos los tests ejecutados
- ✅ Tiempo total de ejecución
- ✅ Estadísticas del evento de prueba
- ✅ Ruta del archivo CSV con los ganadores exportados

### Ejecutar Múltiples Rifas

Para probar el sistema con múltiples rifas y validar la consistencia, puedes ejecutar el comando `test:single-raffle` varias veces:

```bash
# Ejecutar múltiples rifas manualmente
for i in {1..10}; do
  echo "Ejecutando rifa #$i"
  php artisan test:single-raffle
done
```

### Comandos de Exportación e Importación

**Exportar invitados de un evento:**
```bash
php artisan guests:export {event_id}
```

**Exportar premios de un evento:**
```bash
php artisan prizes:export {event_id}
```

**Exportar ganadores de un evento:**
```bash
php artisan winners:export {event_id}
```

**Exportar números de empleado de asistencias:**
```bash
php artisan attendances:export-ids {event_id}
```

**Importar asistencias desde archivo:**
```bash
php artisan attendance:import-ids {event_id} {ruta_archivo.txt}
```

---

Para más detalles sobre el progreso del proyecto, consulta [PROJECT_STATUS.md](PROJECT_STATUS.md)