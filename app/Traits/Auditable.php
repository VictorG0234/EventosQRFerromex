<?php

namespace App\Traits;

use App\Models\AuditLog;

trait Auditable
{
    /**
     * Boot the auditable trait
     */
    protected static function bootAuditable()
    {
        // Registrar creación
        static::created(function ($model) {
            AuditLog::log(
                action: 'created',
                model: class_basename($model),
                modelId: $model->id,
                description: class_basename($model) . " creado: {$model->getAuditDescription()}",
                newValues: $model->getAuditableAttributes()
            );
        });

        // Registrar actualización
        static::updated(function ($model) {
            $changes = $model->getChanges();
            $original = [];
            
            foreach (array_keys($changes) as $key) {
                if (isset($model->getOriginal()[$key])) {
                    $original[$key] = $model->getOriginal()[$key];
                }
            }

            if (!empty($changes)) {
                AuditLog::log(
                    action: 'updated',
                    model: class_basename($model),
                    modelId: $model->id,
                    description: class_basename($model) . " actualizado: {$model->getAuditDescription()}",
                    oldValues: $original,
                    newValues: $changes
                );
            }
        });

        // Registrar eliminación
        static::deleted(function ($model) {
            AuditLog::log(
                action: 'deleted',
                model: class_basename($model),
                modelId: $model->id,
                description: class_basename($model) . " eliminado: {$model->getAuditDescription()}",
                oldValues: $model->getAuditableAttributes()
            );
        });
    }

    /**
     * Obtener descripción del modelo para auditoría
     */
    public function getAuditDescription(): string
    {
        // Priorizar name, luego title, luego id
        if (isset($this->name)) {
            return $this->name;
        }
        
        if (isset($this->title)) {
            return $this->title;
        }
        
        if (isset($this->employee_number)) {
            return $this->employee_number;
        }

        return "ID: {$this->id}";
    }

    /**
     * Obtener atributos auditables (excluir timestamps y campos sensibles)
     */
    public function getAuditableAttributes(): array
    {
        $attributes = $this->getAttributes();
        
        // Excluir campos que no queremos auditar
        $excluded = ['created_at', 'updated_at', 'password', 'remember_token'];
        
        foreach ($excluded as $field) {
            unset($attributes[$field]);
        }
        
        return $attributes;
    }
}
