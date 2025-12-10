<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Guest;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

class GuestImportService
{
    protected QrCodeService $qrCodeService;

    public function __construct(QrCodeService $qrCodeService)
    {
        $this->qrCodeService = $qrCodeService;
    }

    /**
     * Importar invitados desde un archivo CSV
     */
    public function importFromCsv(UploadedFile $file, Event $event): array
    {
        $results = [
            'success' => [],
            'errors' => [],
            'warnings' => [],
            'total' => 0,
            'imported' => 0,
        ];

        try {
            // Leer el archivo CSV
            $data = Excel::toCollection(null, $file)->first();
            
            // Detectar y saltar headers si existen
            $firstRow = $data->first()->toArray();
            $hasHeaders = $this->hasHeaders($firstRow);
            $dataRows = $hasHeaders ? $data->skip(1) : $data;
            
            $results['total'] = $dataRows->count();

            foreach ($dataRows as $index => $row) {
                $rowNumber = $index + ($hasHeaders ? 2 : 1);
                
                try {
                    // Validar los datos de la fila
                    $validatedData = $this->validateRow($row->toArray(), $rowNumber);
                    
                    // Verificar si hubo conversión de fecha
                    if (isset($validatedData['_fecha_convertida']) && $validatedData['_fecha_convertida']) {
                        $results['warnings'][] = [
                            'row' => $rowNumber,
                            'field' => 'fecha_alta',
                            'message' => "Fecha convertida de '{$validatedData['_fecha_original']}' a '{$validatedData['fecha_alta']}'",
                        ];
                        // Limpiar los campos temporales
                        unset($validatedData['_fecha_convertida'], $validatedData['_fecha_original']);
                    }
                    
                    // Crear el invitado
                    $guest = $this->createGuest($event, $validatedData);
                    
                    // Generar código QR
                    $this->qrCodeService->generateQrCode($guest);
                    
                    $results['success'][] = [
                        'row' => $rowNumber,
                        'guest' => $guest,
                        'message' => 'Invitado importado correctamente',
                    ];
                    
                    $results['imported']++;
                    
                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'row' => $rowNumber,
                        'data' => $row->toArray(),
                        'error' => $e->getMessage(),
                    ];
                }
            }
            
        } catch (\Exception $e) {
            $results['errors'][] = [
                'row' => 0,
                'data' => [],
                'error' => 'Error al procesar el archivo: ' . $e->getMessage(),
            ];
        }

        return $results;
    }

    /**
     * Validar una fila del CSV
     */
    protected function validateRow(array $row, int $rowNumber): array
    {
        // Mapear las columnas esperadas
        $mappedData = $this->mapColumns($row);
        
        // Convertir numero_empleado a string si es necesario
        if (isset($mappedData['numero_empleado']) && !is_string($mappedData['numero_empleado'])) {
            $mappedData['numero_empleado'] = (string) $mappedData['numero_empleado'];
        }
        
        // Guardar fecha original para comparar
        $originalFecha = $mappedData['fecha_alta'] ?? null;
        
        // Normalizar fecha antes de validar
        if (!empty($mappedData['fecha_alta'])) {
            $fechaNormalizada = $this->normalizeFecha($mappedData['fecha_alta']);
            if ($fechaNormalizada) {
                $mappedData['fecha_alta'] = $fechaNormalizada;
                // Marcar si la fecha fue convertida
                if ($originalFecha !== $fechaNormalizada) {
                    $mappedData['_fecha_convertida'] = true;
                    $mappedData['_fecha_original'] = $originalFecha;
                }
            }
        }
        
        // Validar los datos (ahora la fecha ya está normalizada)
        $validator = Validator::make($mappedData, [
            'compania' => 'required|string|max:255',
            'numero_empleado' => 'required|string|max:255',
            'nombre_completo' => 'required|string|max:255',
            'correo' => 'nullable|email|max:255',
            'puesto' => 'required|string|max:255',
            'nivel_de_puesto' => 'nullable|string|max:255',
            'localidad' => 'required|string|max:255',
            'fecha_alta' => 'nullable|date',
            'descripcion' => 'nullable|string',
            'categoria_rifa' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            throw new \Exception("Fila $rowNumber: " . implode(', ', $validator->errors()->all()));
        }

        return $mappedData;
    }

    /**
     * Mapear las columnas del CSV a los campos esperados
     */
    protected function mapColumns(array $row): array
    {
        // Si el array es indexado numéricamente, mapear por posición
        if (array_keys($row) === range(0, count($row) - 1)) {
            return [
                'compania' => $row[0] ?? '',
                'numero_empleado' => $row[1] ?? '',
                'nombre_completo' => $row[2] ?? '',
                'correo' => $row[3] ?? '',
                'puesto' => $row[4] ?? '',
                'nivel_de_puesto' => $row[5] ?? '',
                'localidad' => $row[6] ?? '',
                'fecha_alta' => $row[7] ?? '',
                'descripcion' => $row[8] ?? '',
                'categoria_rifa' => $row[9] ?? '',
            ];
        }

        // Si tiene headers, mapear por nombre de columna (con múltiples variaciones)
        $mapped = [
            'compania' => $row['Compañía'] ?? $row['Compañia'] ?? $row['Compania'] ?? $row['compania'] ?? '',
            'numero_empleado' => $row['NumEmp'] ?? $row['NumEmpleado'] ?? $row['numero_empleado'] ?? '',
            'nombre_completo' => $row['Nombre completo'] ?? $row['NombreCompleto'] ?? $row['nombre_completo'] ?? '',
            'correo' => $row['Correo'] ?? $row['correo'] ?? '',
            'puesto' => $row['Puesto'] ?? $row['puesto'] ?? '',
            'nivel_de_puesto' => $row['Nivel de puesto'] ?? $row['NivelDePuesto'] ?? $row['nivel_de_puesto'] ?? '',
            'localidad' => $row['Localidad'] ?? $row['localidad'] ?? '',
            'fecha_alta' => $row['Fecha de alta'] ?? $row['FechaAlta'] ?? $row['fecha_alta'] ?? '',
            'descripcion' => $row['Descripcion'] ?? $row['Descripción'] ?? $row['descripcion'] ?? '',
            'categoria_rifa' => $row['Categoria para la rifa'] ?? $row['CategoriaRifa'] ?? $row['categoria_rifa'] ?? '',
        ];
        
        // Normalizar fecha si existe
        if (!empty($mapped['fecha_alta'])) {
            $mapped['fecha_alta'] = $this->normalizeFecha($mapped['fecha_alta']);
        }
        
        return $mapped;
    }
    
    /**
     * Normalizar fecha desde diferentes formatos a Y-m-d
     */
    protected function normalizeFecha($fecha): ?string
    {
        if (empty($fecha)) {
            return null;
        }
        
        // Si ya está en formato correcto
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return $fecha;
        }
        
        // Intentar parsear diferentes formatos comunes
        $formatos = [
            'd/m/Y',    // 30/5/2016
            'd-m-Y',    // 30-5-2016
            'm/d/Y',    // 5/30/2016
            'Y/m/d',    // 2016/5/30
            'd/m/y',    // 30/5/16
        ];
        
        foreach ($formatos as $formato) {
            $date = \DateTime::createFromFormat($formato, $fecha);
            if ($date !== false) {
                return $date->format('Y-m-d');
            }
        }
        
        // Si no se pudo parsear, intentar con strtotime
        $timestamp = strtotime($fecha);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }
        
        return null;
    }

    /**
     * Crear un invitado
     */
    protected function createGuest(Event $event, array $data): Guest
    {
        // Verificar si ya existe un invitado con la misma combinación de compañía + número de empleado
        $existingGuest = Guest::where('event_id', $event->id)
            ->where('numero_empleado', $data['numero_empleado'])
            ->where('compania', $data['compania'])
            ->first();

        if ($existingGuest) {
            throw new \Exception("Ya existe un invitado con el número de empleado {$data['numero_empleado']} en la compañía {$data['compania']}");
        }

        return Guest::create([
            'event_id' => $event->id,
            'compania' => $data['compania'],
            'numero_empleado' => $data['numero_empleado'],
            'nombre_completo' => $data['nombre_completo'],
            'correo' => $data['correo'],
            'puesto' => $data['puesto'],
            'nivel_de_puesto' => $data['nivel_de_puesto'],
            'localidad' => $data['localidad'],
            'fecha_alta' => $data['fecha_alta'],
            'descripcion' => $data['descripcion'],
            'categoria_rifa' => $data['categoria_rifa'],
        ]);
    }

    /**
     * Obtener template CSV de ejemplo
     */
    public function getCsvTemplate(): array
    {
        return [
            'headers' => [
                'Compañia',
                'NumEmpleado',
                'NombreCompleto',
                'Correo',
                'Puesto',
                'NivelDePuesto',
                'Localidad',
                'FechaAlta',
                'Descripcion',
                'CategoriaRifa'
            ],
            'example_data' => [
                [
                    'Ferromex',
                    'EMP001',
                    'Juan Pérez García',
                    'juan.perez@ferromex.com',
                    'Ingeniero',
                    'Senior',
                    'Guadalajara',
                    '2020-01-15',
                    'Empleado del área de sistemas',
                    'Premium'
                ],
                [
                    'Ferromex',
                    'EMP002',
                    'María López Martínez',
                    'maria.lopez@ferromex.com',
                    'Gerente',
                    'Ejecutivo',
                    'Ciudad de México',
                    '2018-03-20',
                    'Gerente de recursos humanos',
                    'VIP'
                ]
            ]
        ];
    }

    /**
     * Vista previa del archivo CSV antes de importar
     */
    public function previewCsv(UploadedFile $file, int $maxRows = 10): array
    {
        try {
            $data = Excel::toCollection(null, $file)->first();
            
            if ($data->isEmpty()) {
                throw new \Exception('El archivo CSV está vacío');
            }
            
            // Detectar si la primera fila son headers
            $firstRow = $data->first()->toArray();
            $hasHeaders = $this->hasHeaders($firstRow);
            
            // Si tiene headers, saltarlos para el preview
            $dataRows = $hasHeaders ? $data->skip(1) : $data;
            
            $preview = [
                'total_rows' => $dataRows->count(),
                'has_headers' => $hasHeaders,
                'headers' => $hasHeaders ? $this->detectHeaders($firstRow) : [],
                'sample_data' => $dataRows->take($maxRows)->map(function($row, $index) use ($hasHeaders) {
                    $mappedData = $this->mapColumns($row->toArray());
                    
                    // Detectar advertencias de conversión de fecha
                    $warnings = [];
                    if (isset($mappedData['_fecha_convertida']) && $mappedData['_fecha_convertida']) {
                        $warnings[] = "Fecha convertida de '{$mappedData['_fecha_original']}' a '{$mappedData['fecha_alta']}'";
                    }
                    
                    return [
                        'row_number' => $index + ($hasHeaders ? 2 : 1),
                        'data' => $row->toArray(),
                        'mapped' => $mappedData,
                        'valid' => $this->isRowValid($row->toArray()),
                        'warnings' => $warnings
                    ];
                })->toArray(),
                'validation_summary' => $this->getValidationSummary($dataRows)
            ];

            return $preview;

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Preview CSV error: ' . $e->getMessage());
            throw new \Exception('Error al procesar el archivo: ' . $e->getMessage());
        }
    }

    /**
     * Detectar si la primera fila contiene headers
     */
    protected function hasHeaders(array $firstRow): bool
    {
        // Si algún valor de la primera fila coincide con un nombre de campo esperado, son headers
        $expectedHeaders = [
            'compania', 'compañia', 'compañía', 
            'numemp', 'numempleado', 'numeroempleado',
            'nombrecompleto', 'nombre completo',
            'correo', 'puesto', 'localidad',
            'niveldepuesto', 'nivel de puesto',
            'fechaalta', 'fecha de alta',
            'categoriarifa', 'categoria para la rifa'
        ];
        
        foreach ($firstRow as $value) {
            // Ignorar valores null o vacíos
            if (empty($value)) {
                continue;
            }
            
            $normalized = strtolower(str_replace([' ', '_'], '', trim($value)));
            if (in_array($normalized, $expectedHeaders)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Detectar headers del CSV
     */
    protected function detectHeaders(array $firstRow): array
    {
        $detectedHeaders = [];
        
        foreach ($firstRow as $index => $value) {
            // Convertir null a string vacío
            $value = $value ?? '';
            $mappedField = $this->getFieldMapping($value, $index);
            
            $detectedHeaders[$index] = [
                'original' => $value,
                'mapped_to' => $mappedField,
                'required' => in_array($mappedField, [
                    'compania', 'numero_empleado', 'nombre_completo', 'puesto', 'localidad'
                ])
            ];
        }

        return $detectedHeaders;
    }

    /**
     * Mapear nombre de columna a campo
     */
    protected function getFieldMapping(?string $header, int $index): string
    {
        // Si el header es null o vacío, usar mapeo por posición
        if (empty($header)) {
            $positionMap = ['compania', 'numero_empleado', 'nombre_completo', 'correo', 'puesto', 'nivel_de_puesto', 'localidad', 'fecha_alta', 'descripcion', 'categoria_rifa'];
            return $positionMap[$index] ?? 'unknown_' . $index;
        }
        
        $header = strtolower(trim($header));
        
        $mappings = [
            'compañía' => 'compania',
            'compañia' => 'compania',
            'compania' => 'compania',
            'empresa' => 'compania',
            'numemp' => 'numero_empleado',
            'numempleado' => 'numero_empleado',
            'numero_empleado' => 'numero_empleado',
            'numero empleado' => 'numero_empleado',
            'numeroempleado' => 'numero_empleado',
            'nombre completo' => 'nombre_completo',
            'nombrecompleto' => 'nombre_completo',
            'nombre_completo' => 'nombre_completo',
            'correo' => 'correo',
            'email' => 'correo',
            'e-mail' => 'correo',
            'puesto' => 'puesto',
            'cargo' => 'puesto',
            'nivel de puesto' => 'nivel_de_puesto',
            'niveldepuesto' => 'nivel_de_puesto',
            'nivel_de_puesto' => 'nivel_de_puesto',
            'localidad' => 'localidad',
            'ubicacion' => 'localidad',
            'ciudad' => 'localidad',
            'fecha de alta' => 'fecha_alta',
            'fechaalta' => 'fecha_alta',
            'fecha_alta' => 'fecha_alta',
            'descripcion' => 'descripcion',
            'descripción' => 'descripcion',
            'categoria para la rifa' => 'categoria_rifa',
            'categoriarifa' => 'categoria_rifa',
            'categoria_rifa' => 'categoria_rifa',
            'categoria rifa' => 'categoria_rifa',
        ];

        if (isset($mappings[$header])) {
            return $mappings[$header];
        }

        // Mapeo por posición si no hay header reconocido
        $positionMap = ['compania', 'numero_empleado', 'nombre_completo', 'correo', 'puesto', 'nivel_de_puesto', 'localidad', 'fecha_alta', 'descripcion', 'categoria_rifa'];
        return $positionMap[$index] ?? 'unknown_' . $index;
    }

    /**
     * Verificar si una fila es válida
     */
    protected function isRowValid(array $row): array
    {
        try {
            $this->validateRow($row, 0);
            return ['valid' => true, 'errors' => []];
        } catch (\Exception $e) {
            return ['valid' => false, 'errors' => [$e->getMessage()]];
        }
    }

    /**
     * Obtener resumen de validación
     */
    protected function getValidationSummary(Collection $data): array
    {
        $valid = 0;
        $invalid = 0;
        $errors = [];

        foreach ($data as $index => $row) {
            $validation = $this->isRowValid($row->toArray());
            if ($validation['valid']) {
                $valid++;
            } else {
                $invalid++;
                $errors[] = [
                    'row' => $index + 1,
                    'errors' => $validation['errors']
                ];
            }
        }

        return [
            'total_rows' => $data->count(),
            'valid_rows' => $valid,
            'invalid_rows' => $invalid,
            'errors' => array_slice($errors, 0, 10) // Solo primeros 10 errores
        ];
    }
}