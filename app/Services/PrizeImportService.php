<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Prize;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

class PrizeImportService
{
    /**
     * Importar premios desde un archivo CSV
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
                    
                    // Crear N premios según la cantidad
                    $quantity = (int) $validatedData['stock'];
                    $createdPrizes = $this->createPrize($event, $validatedData);
                    
                    // Si se crearon múltiples premios, los convertimos en array
                    $prizesArray = is_array($createdPrizes) ? $createdPrizes : [$createdPrizes];
                    
                    $results['success'][] = [
                        'row' => $rowNumber,
                        'prize' => $createdPrizes,
                        'prizes_created' => count($prizesArray),
                        'message' => $quantity > 1 
                            ? "Se crearon {$quantity} registros del premio correctamente" 
                            : 'Premio importado correctamente',
                    ];
                    
                    $results['imported'] += count($prizesArray);
                    
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
        
        // Validar los datos
        $validator = Validator::make($mappedData, [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'nullable|string|max:255',
            'stock' => 'required|integer|min:1|max:1000',
            'value' => 'nullable|numeric|min:0|max:999999.99',
            'active' => 'nullable|boolean',
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
                'name' => $row[0] ?? '',
                'description' => $row[1] ?? null,
                'category' => $row[2] ?? null,
                'stock' => $row[3] ?? '',
                'value' => $row[4] ?? null,
                'active' => $this->parseActive($row[5] ?? 'Sí'),
            ];
        }

        // Si tiene headers, mapear por nombre de columna (con múltiples variaciones)
        $mapped = [
            'name' => $row['Titulo'] ?? $row['Título'] ?? $row['titulo'] ?? $row['Nombre'] ?? $row['nombre'] ?? $row['name'] ?? '',
            'description' => $row['Descripcion'] ?? $row['Descripción'] ?? $row['descripcion'] ?? $row['description'] ?? null,
            'category' => $row['Categoria'] ?? $row['Categoría'] ?? $row['categoria'] ?? $row['category'] ?? null,
            'stock' => $row['Cantidad'] ?? $row['cantidad'] ?? $row['Stock'] ?? $row['stock'] ?? '',
            'value' => $row['Valor'] ?? $row['valor'] ?? $row['value'] ?? null,
            'active' => $this->parseActive($row['Activo'] ?? $row['activo'] ?? $row['Active'] ?? $row['active'] ?? 'Sí'),
        ];
        
        return $mapped;
    }

    /**
     * Parsear el valor de activo desde diferentes formatos
     */
    protected function parseActive($value): bool
    {
        if (empty($value)) {
            return true; // Por defecto activo
        }

        $value = strtolower(trim($value));
        
        // Valores que indican activo
        $activeValues = ['sí', 'si', 'yes', '1', 'true', 'verdadero', 'activo'];
        
        return in_array($value, $activeValues);
    }

    /**
     * Crear uno o múltiples premios según la cantidad
     */
    protected function createPrize(Event $event, array $data)
    {
        $quantity = (int) $data['stock'];
        $prizeData = [
            'event_id' => $event->id,
            'name' => $data['name'],
            'description' => $data['description'],
            'category' => $data['category'],
            'value' => $data['value'],
            'active' => $data['active'] ?? true,
        ];

        // Si la cantidad es mayor a 1, crear N registros
        if ($quantity > 1) {
            $prizes = [];
            for ($i = 0; $i < $quantity; $i++) {
                $prizeData['stock'] = 1;
                $prizes[] = Prize::create($prizeData);
            }
            return $prizes;
        }

        // Si la cantidad es 1, crear un solo registro
        $prizeData['stock'] = 1;
        return Prize::create($prizeData);
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
                    
                    return [
                        'row_number' => $index + ($hasHeaders ? 2 : 1),
                        'data' => $row->toArray(),
                        'mapped' => $mappedData,
                        'valid' => $this->isRowValid($row->toArray()),
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
            'titulo', 'título', 'nombre', 'name',
            'descripcion', 'descripción', 'description',
            'categoria', 'categoría', 'category',
            'cantidad', 'stock',
            'valor', 'value',
            'activo', 'active'
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
                'required' => in_array($mappedField, ['name', 'stock'])
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
            $positionMap = ['name', 'description', 'category', 'stock', 'value', 'active'];
            return $positionMap[$index] ?? 'unknown_' . $index;
        }
        
        $header = strtolower(trim($header));
        
        $mappings = [
            'titulo' => 'name',
            'título' => 'name',
            'nombre' => 'name',
            'name' => 'name',
            'descripcion' => 'description',
            'descripción' => 'description',
            'description' => 'description',
            'categoria' => 'category',
            'categoría' => 'category',
            'category' => 'category',
            'cantidad' => 'stock',
            'stock' => 'stock',
            'valor' => 'value',
            'value' => 'value',
            'activo' => 'active',
            'active' => 'active',
        ];

        if (isset($mappings[$header])) {
            return $mappings[$header];
        }

        // Mapeo por posición si no hay header reconocido
        $positionMap = ['name', 'description', 'category', 'stock', 'value', 'active'];
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

    /**
     * Obtener template CSV de ejemplo
     */
    public function getCsvTemplate(): array
    {
        return [
            'headers' => [
                'Titulo',
                'Descripcion',
                'Categoria',
                'Cantidad',
                'Valor',
                'Activo'
            ],
            'example_data' => [
                [
                    'iPhone 15 Pro',
                    'Smartphone de última generación',
                    'Electrónicos',
                    '5',
                    '25000.00',
                    'Sí'
                ],
                [
                    'Vale de Amazon',
                    'Para compras en línea',
                    'Electrónicos',
                    '10',
                    '5000.00',
                    'Sí'
                ],
                [
                    'Cena para dos',
                    'En restaurante de lujo',
                    'Gastronómicos',
                    '3',
                    '2000.00',
                    'Sí'
                ],
            ]
        ];
    }
}


