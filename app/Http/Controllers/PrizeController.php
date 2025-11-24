<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Prize;
use App\Services\RaffleService;
use App\Services\PrizeImportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class PrizeController extends Controller
{
    protected $raffleService;
    protected $importService;

    public function __construct(RaffleService $raffleService, PrizeImportService $importService)
    {
        $this->raffleService = $raffleService;
        $this->importService = $importService;
    }

    /**
     * Display a listing of prizes for an event.
     */
    public function index(Event $event)
    {
        $this->authorize('view', $event);

        $prizes = $event->prizes()
            ->where('name', '!=', 'Rifa General') // Excluir el premio especial
            ->withCount(['raffleEntries', 'raffleEntries as winners_count' => function ($query) {
                $query->where('status', 'won');
            }])
            ->latest()
            ->get()
            ->map(function ($prize) {
                return [
                    'id' => $prize->id,
                    'name' => $prize->name,
                    'description' => $prize->description,
                    'category' => $prize->category,
                    'stock' => $prize->stock,
                    'value' => $prize->value,
                    'active' => $prize->active,
                    'image' => $prize->image,
                    'stock_percentage' => $prize->getStockPercentage(),
                    'participants_count' => $prize->raffle_entries_count,
                    'winners_count' => $prize->winners_count,
                    'is_available' => $prize->isAvailable(),
                    'created_at' => $prize->created_at->format('d/m/Y H:i')
                ];
            });

        $statistics = $this->raffleService->getRaffleStatistics($event);

        return Inertia::render('Events/Prizes/Index', [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
            ],
            'prizes' => $prizes,
            'statistics' => $statistics
        ]);
    }

    /**
     * Show the form for creating a new prize.
     */
    public function create(Event $event)
    {
        $this->authorize('update', $event);

        return Inertia::render('Events/Prizes/Create', [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
            ],
        ]);
    }

    /**
     * Store a newly created prize.
     */
    public function store(Request $request, Event $event)
    {
        $this->authorize('update', $event);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'nullable|string|max:255',
            'stock' => 'required|integer|min:1|max:1000',
            'value' => 'nullable|numeric|min:0|max:999999.99',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'active' => 'boolean'
        ]);

        $validated['event_id'] = $event->id;
        $validated['active'] = $request->boolean('active', true);

        // Handle image upload
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('prizes', 'public');
            $validated['image'] = $imagePath;
        }

        // Crear N registros si la cantidad es mayor a 1
        $quantity = $validated['stock'];
        $prizes = [];
        $totalEntriesCreated = 0;
        
        for ($i = 0; $i < $quantity; $i++) {
            $prizeData = $validated;
            $prizeData['stock'] = 1;
            $prize = Prize::create($prizeData);
            $prizes[] = $prize;
            
            // Crear participaciones automáticamente para todos los invitados elegibles
            // Solo si el premio está activo
            if ($prize->active) {
                try {
                    $result = $this->raffleService->createRaffleEntries($prize, 'general');
                    if ($result['success']) {
                        $totalEntriesCreated += $result['entries_created'];
                    }
                } catch (\Exception $e) {
                    // Error al crear participaciones automáticas, pero no fallar la creación del premio
                }
            }
        }

        $message = $quantity > 1 
            ? "Se crearon {$quantity} registros del premio exitosamente." 
            : 'Premio creado exitosamente.';
            
        if ($totalEntriesCreated > 0) {
            $message .= " Se crearon {$totalEntriesCreated} participaciones automáticamente para los invitados elegibles.";
        }

        return redirect()->route('events.prizes.index', $event)
            ->with('success', $message);
    }

    /**
     * Display the specified prize.
     */
    public function show(Event $event, Prize $prize)
    {
        $this->authorize('view', $event);

        if ($prize->event_id !== $event->id) {
            abort(404);
        }

        $results = $this->raffleService->getPrizeResults($prize);
        $eligibleGuests = $this->raffleService->getEligibleGuests($prize);
        $validation = $this->raffleService->validatePrize($prize);

        return Inertia::render('Events/Prizes/Show', [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
            ],
            'prize' => [
                'id' => $prize->id,
                'name' => $prize->name,
                'description' => $prize->description,
                'category' => $prize->category,
                'stock' => $prize->stock,
                'value' => $prize->value,
                'active' => $prize->active,
                'image' => $prize->image,
                'stock_percentage' => $prize->getStockPercentage(),
                'created_at' => $prize->created_at->format('d/m/Y H:i')
            ],
            'results' => $results,
            'eligible_guests' => $eligibleGuests->map(function ($guest) {
                return [
                    'id' => $guest->id,
                    'name' => $guest->full_name,
                    'employee_number' => $guest->numero_empleado,
                    'work_area' => $guest->puesto,
                    'attended_at' => $guest->attendance?->created_at->format('d/m/Y H:i')
                ];
            }),
            'validation' => $validation
        ]);
    }

    /**
     * Show the form for editing the specified prize.
     */
    public function edit(Event $event, Prize $prize)
    {
        $this->authorize('update', $event);

        if ($prize->event_id !== $event->id) {
            abort(404);
        }

        // Check if prize has raffle entries
        $hasEntries = $prize->raffleEntries()->exists();

        // Get categories
        $categories = $event->guests()
            ->whereNotNull('categoria_rifa')
            ->pluck('categoria_rifa')
            ->unique()
            ->filter()
            ->values();

        return Inertia::render('Events/Prizes/Edit', [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
            ],
            'prize' => [
                'id' => $prize->id,
                'name' => $prize->name,
                'description' => $prize->description,
                'category' => $prize->category,
                'stock' => $prize->stock,
                'value' => $prize->value,
                'active' => $prize->active,
                'image' => $prize->image
            ],
            'categories' => $categories,
            'has_entries' => $hasEntries
        ]);
    }

    /**
     * Update the specified prize.
     */
    public function update(Request $request, Event $event, Prize $prize)
    {
        $this->authorize('update', $event);

        if ($prize->event_id !== $event->id) {
            abort(404);
        }

        $hasEntries = $prize->raffleEntries()->exists();

        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'value' => 'nullable|numeric|min:0|max:999999.99',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'active' => 'boolean'
        ];

        // Only allow category and stock changes if no entries exist
        if (!$hasEntries) {
            $rules['category'] = 'nullable|string|max:255';
            $rules['stock'] = 'required|integer|min:1|max:1000';
        }

        $validated = $request->validate($rules);
        $validated['active'] = $request->boolean('active');

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image
            if ($prize->image) {
                Storage::disk('public')->delete($prize->image);
            }
            $imagePath = $request->file('image')->store('prizes', 'public');
            $validated['image'] = $imagePath;
        }


        $prize->update($validated);

        return redirect()->route('events.prizes.index', $event)
            ->with('success', 'Premio actualizado exitosamente.');
    }

    /**
     * Remove the specified prize.
     */
    public function destroy(Event $event, Prize $prize)
    {
        $this->authorize('update', $event);

        if ($prize->event_id !== $event->id) {
            abort(404);
        }

        // Check if prize has raffle entries
        if ($prize->raffleEntries()->exists()) {
            return back()->with('error', 'No se puede eliminar un premio que ya tiene participaciones en rifas.');
        }

        // Delete image if exists
        if ($prize->image) {
            Storage::disk('public')->delete($prize->image);
        }

        $prize->delete();

        return redirect()->route('events.prizes.index', $event)
            ->with('success', 'Premio eliminado exitosamente.');
    }

    /**
     * Toggle prize active status.
     */
    public function toggleActive(Event $event, Prize $prize)
    {
        $this->authorize('update', $event);

        if ($prize->event_id !== $event->id) {
            abort(404);
        }

        $prize->update(['active' => !$prize->active]);

        $status = $prize->active ? 'activado' : 'desactivado';

        return back()->with('success', "Premio {$status} exitosamente.");
    }

    /**
     * Download prize template CSV.
     */
    public function downloadTemplate()
    {
        // Agregar BOM UTF-8 para que Excel reconozca correctamente los acentos
        $bom = "\xEF\xBB\xBF";
        
        $csvContent = [
            ['Titulo', 'Descripcion', 'Categoria', 'Cantidad', 'Valor', 'Activo'],
            ['iPhone 15 Pro', 'Smartphone de última generación', '', '5', '25000.00', 'Sí'],
            ['Vale de Amazon', 'Para compras en línea', '', '10', '5000.00', 'Sí'],
            ['Cena para dos', 'En restaurante de lujo', '', '3', '2000.00', 'Sí'],
        ];

        // Abrir un handle de memoria
        $handle = fopen('php://temp', 'r+');
        
        // Escribir cada fila usando fputcsv que maneja correctamente la codificación
        foreach ($csvContent as $row) {
            fputcsv($handle, $row, ',', '"');
        }
        
        // Obtener el contenido
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);
        
        // Agregar BOM al inicio
        $csv = $bom . $csv;

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="plantilla_premios.csv"',
        ]);
    }

    /**
     * Show CSV import form.
     */
    public function importForm(Event $event)
    {
        $this->authorize('view', $event);

        return Inertia::render('Events/Prizes/Import', [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'prizes_count' => $event->prizes()->count(),
            ]
        ]);
    }

    /**
     * Process CSV import.
     */
    public function import(Request $request, Event $event)
    {
        $this->authorize('update', $event);

        // Aumentar tiempo de ejecución para archivos grandes
        set_time_limit(300); // 5 minutos
        ini_set('max_execution_time', '300');

        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240', // Max 10MB
        ]);

        try {
            $result = $this->importService->importFromCsv(
                $request->file('csv_file'),
                $event
            );

            $errorsCount = count($result['errors']);
            $warningsCount = count($result['warnings'] ?? []);
            $message = "Importación completada: {$result['imported']} premios importados.";
            
            if ($errorsCount > 0) {
                $message .= " {$errorsCount} registro(s) con errores.";
            }
            
            if ($warningsCount > 0) {
                $message .= " {$warningsCount} advertencia(s).";
            }

            return redirect()->route('events.prizes.index', $event)
                ->with('success', $message)
                ->with('import_results', $result);

        } catch (\Exception $e) {
            return back()->with('error', 'Error al importar el archivo: ' . $e->getMessage());
        }
    }

    /**
     * Preview CSV file before import.
     */
    public function preview(Request $request, Event $event)
    {
        $this->authorize('view', $event);

        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        try {
            $preview = $this->importService->previewCsv($request->file('csv_file'));

            return response()->json([
                'success' => true,
                'data' => $preview
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }
}