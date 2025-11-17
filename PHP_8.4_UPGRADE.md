# Guía de Actualización a PHP 8.4

## Cambios Realizados

Este proyecto ha sido actualizado para ser compatible con PHP 8.4 y versiones superiores.

### 1. Cambios en composer.json

- **Versión de PHP**: Actualizada de `^8.1` a `^8.2|^8.3|^8.4`
- **Laravel Framework**: Actualizado a `^10.48.22` (última versión de Laravel 10 con soporte PHP 8.4)
- **Dependencias de desarrollo**: Actualizadas a versiones compatibles con PHP 8.4

### 2. Tipos Nullable Explícitos (PHP 8.4 Deprecation)

En PHP 8.4, marcar parámetros como nullable de forma implícita está deprecado. Todos los parámetros con tipo y valor por defecto `null` deben usar el operador `?` explícitamente.

#### Archivos Modificados:

**app/Models/Attendance.php**
```php
// Antes (deprecado en PHP 8.4)
public static function registerAttendance(Guest $guest, string $scannedBy = null, array $metadata = []): self

// Después (compatible con PHP 8.4)
public static function registerAttendance(Guest $guest, ?string $scannedBy = null, array $metadata = []): self
```

**app/Jobs/SendEmailJob.php**
```php
// Antes
public function __construct(string $emailType, $guest = null, $event = null, array $additionalData = [])

// Después
public function __construct(string $emailType, ?Guest $guest = null, ?Event $event = null, array $additionalData = [])
```

### 3. Versiones de Dependencias Actualizadas

| Paquete | Versión Anterior | Versión Nueva |
|---------|------------------|---------------|
| fakerphp/faker | ^1.9.1 | ^1.23 |
| laravel/pint | ^1.0 | ^1.13 |
| laravel/sail | ^1.18 | ^1.26 |
| mockery/mockery | ^1.4.4 | ^1.6 |
| nunomaduro/collision | ^7.0 | ^7.10\|^8.0 |
| phpunit/phpunit | ^10.1 | ^10.5\|^11.0 |
| spatie/laravel-ignition | ^2.0 | ^2.4 |

## Cómo Actualizar

### 1. Verificar Versión de PHP

```bash
php -v
```

Debes tener PHP 8.2, 8.3 o 8.4 instalado.

### 2. Actualizar Dependencias

```bash
# Eliminar dependencias antiguas
rm -rf vendor composer.lock

# Instalar nuevas dependencias
composer install
```

### 3. Limpiar Caché

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### 4. Ejecutar Migraciones (si es necesario)

```bash
php artisan migrate
```

### 5. Compilar Assets

```bash
npm install
npm run build
```

## Problemas Conocidos

### Warning de Parámetros Nullable

Si ves warnings como:
```
PHP Deprecated: Implicitly marking parameter $variable as nullable is deprecated
```

Esto indica que hay más parámetros en el código que necesitan ser actualizados con el operador `?`. Los archivos principales ya han sido corregidos.

### Compatibilidad con Laravel 11

Si en el futuro deseas actualizar a Laravel 11, ten en cuenta que:
- Laravel 11 requiere PHP 8.2 como mínimo
- La estructura de archivos cambia significativamente (elimina `app/Http/Kernel.php`, `app/Console/Kernel.php`, etc.)
- Se recomienda seguir la [guía oficial de actualización](https://laravel.com/docs/11.x/upgrade)

## Testing

Después de actualizar, ejecuta los tests para verificar que todo funciona correctamente:

```bash
php artisan test
```

## Notas Adicionales

- **Retrocompatibilidad**: El código actualizado es compatible con PHP 8.2, 8.3 y 8.4
- **Laravel 10**: Se mantiene Laravel 10 para minimizar cambios disruptivos
- **Producción**: Asegúrate de probar en un entorno de staging antes de desplegar a producción

## Recursos

- [PHP 8.4 Release Notes](https://www.php.net/releases/8.4/en.php)
- [Laravel 10 Documentation](https://laravel.com/docs/10.x)
- [PHP 8.4 Deprecations](https://php.watch/versions/8.4)
