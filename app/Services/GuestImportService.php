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
            'total' => 0,
            'imported' => 0,
        ];

        try {
            // Leer el archivo CSV
            $data = Excel::toCollection(null, $file)->first();
            $results['total'] = $data->count();

            foreach ($data as $index => $row) {
                $rowNumber = $index + 1;
                
                try {
                    // Validar los datos de la fila
                    $validatedData = $this->validateRow($row->toArray(), $rowNumber);
                    
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
        
        // Validar los datos
        $validator = Validator::make($mappedData, [
            'nombre' => 'required|string|max:255',
            'apellido_p' => 'required|string|max:255',
            'apellido_m' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'numero_empleado' => 'required|string|max:255',
            'area_laboral' => 'required|string|max:255',
            'premios_rifa' => 'required|string',
        ]);

        if ($validator->fails()) {
            throw new \Exception("Fila $rowNumber: " . implode(', ', $validator->errors()->all()));
        }

        // Procesar premios_rifa como array
        $premiosArray = array_map('trim', explode(',', $mappedData['premios_rifa']));
        $mappedData['premios_rifa'] = array_filter($premiosArray);

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
                'nombre' => $row[0] ?? '',
                'apellido_p' => $row[1] ?? '',
                'apellido_m' => $row[2] ?? '',
                'email' => $row[3] ?? '',
                'numero_empleado' => $row[4] ?? '',
                'area_laboral' => $row[5] ?? '',
                'premios_rifa' => $row[6] ?? '',
            ];
        }

        // Si tiene headers, mapear por nombre de columna
        return [
            'nombre' => $row['Nombre'] ?? $row['nombre'] ?? '',
            'apellido_p' => $row['ApellidoP'] ?? $row['apellido_p'] ?? '',
            'apellido_m' => $row['ApellidoM'] ?? $row['apellido_m'] ?? '',
            'email' => $row['Correo'] ?? $row['correo'] ?? $row['email'] ?? '',
            'numero_empleado' => $row['NumeroEmpleado'] ?? $row['numero_empleado'] ?? '',
            'area_laboral' => $row['AreaLaboral'] ?? $row['area_laboral'] ?? '',
            'premios_rifa' => $row['PremiosRifa'] ?? $row['premios_rifa'] ?? '',
        ];
    }

    /**
     * Crear un invitado
     */
    protected function createGuest(Event $event, array $data): Guest
    {
        // Verificar si ya existe un invitado con el mismo número de empleado
        $existingGuest = Guest::where('event_id', $event->id)
            ->where('numero_empleado', $data['numero_empleado'])
            ->first();

        if ($existingGuest) {
            throw new \Exception("Ya existe un invitado con el número de empleado: {$data['numero_empleado']}");
        }

        return Guest::create([
            'event_id' => $event->id,
            'nombre' => $data['nombre'],
            'apellido_p' => $data['apellido_p'],
            'apellido_m' => $data['apellido_m'],
            'email' => $data['email'],
            'numero_empleado' => $data['numero_empleado'],
            'area_laboral' => $data['area_laboral'],
            'premios_rifa' => $data['premios_rifa'],
        ]);
    }

    /**
     * Obtener template CSV de ejemplo
     */
    public function getCsvTemplate(): array
    {
        return [
            'headers' => [
                'Nombre',
                'ApellidoP',
                'ApellidoM',
                'Correo',
                'NumeroEmpleado',
                'AreaLaboral',
                'PremiosRifa'
            ],
            'example_data' => [
                [
                    'Juan',
                    'Pérez',
                    'García',
                    'juan.perez@empresa.com',
                    'EMP001',
                    'Sistemas',
                    'Categoria1,Categoria2,Categoria3'
                ],
                [
                    'María',
                    'López',
                    'Martínez',
                    'maria.lopez@empresa.com',
                    'EMP002',
                    'Recursos Humanos',
                    'Categoria1,Categoria3'
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
            
            $preview = [
                'total_rows' => $data->count(),
                'headers' => $this->detectHeaders($data->first()?->toArray() ?? []),
                'sample_data' => $data->take($maxRows)->map(function($row, $index) {
                    return [
                        'row_number' => $index + 1,
                        'data' => $row->toArray(),
                        'mapped' => $this->mapColumns($row->toArray()),
                        'valid' => $this->isRowValid($row->toArray())
                    ];
                })->toArray(),
                'validation_summary' => $this->getValidationSummary($data)
            ];

            return $preview;

        } catch (\Exception $e) {
            throw new \Exception('Error al procesar el archivo: ' . $e->getMessage());
        }
    }

    /**
     * Detectar headers del CSV
     */
    protected function detectHeaders(array $firstRow): array
    {
        $detectedHeaders = [];
        
        foreach ($firstRow as $index => $value) {
            $detectedHeaders[$index] = [
                'original' => $value,
                'mapped_to' => $this->getFieldMapping($value, $index),
                'required' => in_array($this->getFieldMapping($value, $index), [
                    'nombre', 'apellido_p', 'apellido_m', 'email', 'numero_empleado', 'area_laboral', 'premios_rifa'
                ])
            ];
        }

        return $detectedHeaders;
    }

    /**
     * Mapear nombre de columna a campo
     */
    protected function getFieldMapping(string $header, int $index): string
    {
        $header = strtolower(trim($header));
        
        $mappings = [
            'nombre' => 'nombre',
            'apellidop' => 'apellido_p',
            'apellido_p' => 'apellido_p',
            'apellido p' => 'apellido_p',
            'apellidom' => 'apellido_m',
            'apellido_m' => 'apellido_m',
            'apellido m' => 'apellido_m',
            'correo' => 'email',
            'email' => 'email',
            'e-mail' => 'email',
            'numeroempleado' => 'numero_empleado',
            'numero_empleado' => 'numero_empleado',
            'numero empleado' => 'numero_empleado',
            'arealaboral' => 'area_laboral',
            'area_laboral' => 'area_laboral',
            'area laboral' => 'area_laboral',
            'premiosrifa' => 'premios_rifa',
            'premios_rifa' => 'premios_rifa',
            'premios rifa' => 'premios_rifa',
        ];

        if (isset($mappings[$header])) {
            return $mappings[$header];
        }

        // Mapeo por posición si no hay header reconocido
        $positionMap = ['nombre', 'apellido_p', 'apellido_m', 'email', 'numero_empleado', 'area_laboral', 'premios_rifa'];
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