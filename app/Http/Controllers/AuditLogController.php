<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AuditLogController extends Controller
{
    /**
     * Mostrar panel de auditoría
     */
    public function index(Request $request)
    {
        $query = AuditLog::with('user')
            ->orderBy('created_at', 'desc');

        // Filtro por acción
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        // Filtro por modelo
        if ($request->filled('model')) {
            $query->where('model', $request->model);
        }

        // Filtro por usuario
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filtro por rango de fechas
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Búsqueda por descripción
        if ($request->filled('search')) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }

        $logs = $query->paginate(50)->through(function ($log) {
            return [
                'id' => $log->id,
                'user' => $log->user ? [
                    'id' => $log->user->id,
                    'name' => $log->user->name,
                    'email' => $log->user->email,
                ] : null,
                'action' => $log->action,
                'action_name' => $log->action_name,
                'model' => $log->model,
                'model_id' => $log->model_id,
                'description' => $log->description,
                'old_values' => $log->old_values,
                'new_values' => $log->new_values,
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'created_at' => $log->created_at->format('d/m/Y H:i:s'),
                'created_at_diff' => $log->created_at->diffForHumans(),
            ];
        });

        // Estadísticas rápidas
        $statistics = [
            'total_today' => AuditLog::today()->count(),
            'total_week' => AuditLog::where('created_at', '>=', now()->subWeek())->count(),
            'total_month' => AuditLog::where('created_at', '>=', now()->subMonth())->count(),
            'actions_today' => AuditLog::today()
                ->selectRaw('action, COUNT(*) as count')
                ->groupBy('action')
                ->get()
                ->mapWithKeys(fn($item) => [$item->action => $item->count]),
        ];

        // Obtener listas para filtros
        $actions = AuditLog::select('action')->distinct()->pluck('action');
        $models = AuditLog::select('model')->distinct()->whereNotNull('model')->pluck('model');

        return Inertia::render('AuditLogs/Index', [
            'logs' => $logs,
            'statistics' => $statistics,
            'actions' => $actions,
            'models' => $models,
            'filters' => $request->only(['action', 'model', 'user_id', 'date_from', 'date_to', 'search']),
        ]);
    }

    /**
     * Mostrar detalle de un log específico
     */
    public function show(AuditLog $auditLog)
    {
        return Inertia::render('AuditLogs/Show', [
            'log' => [
                'id' => $auditLog->id,
                'user' => $auditLog->user ? [
                    'id' => $auditLog->user->id,
                    'name' => $auditLog->user->name,
                    'email' => $auditLog->user->email,
                ] : null,
                'action' => $auditLog->action,
                'action_name' => $auditLog->action_name,
                'model' => $auditLog->model,
                'model_id' => $auditLog->model_id,
                'description' => $auditLog->description,
                'old_values' => $auditLog->old_values,
                'new_values' => $auditLog->new_values,
                'ip_address' => $auditLog->ip_address,
                'user_agent' => $auditLog->user_agent,
                'created_at' => $auditLog->created_at->format('d/m/Y H:i:s'),
            ],
        ]);
    }

    /**
     * Exportar logs a CSV
     */
    public function export(Request $request)
    {
        $query = AuditLog::with('user')
            ->orderBy('created_at', 'desc');

        // Aplicar mismos filtros que en index
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('model')) {
            $query->where('model', $request->model);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->get();

        $filename = 'audit-logs-' . now()->format('Y-m-d-His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($logs) {
            $file = fopen('php://output', 'w');
            
            // Encabezados
            fputcsv($file, ['ID', 'Usuario', 'Email', 'Acción', 'Modelo', 'ID Modelo', 'Descripción', 'IP', 'Fecha']);
            
            // Datos
            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->id,
                    $log->user?->name ?? 'N/A',
                    $log->user?->email ?? 'N/A',
                    $log->action_name,
                    $log->model ?? 'N/A',
                    $log->model_id ?? 'N/A',
                    $log->description ?? 'N/A',
                    $log->ip_address ?? 'N/A',
                    $log->created_at->format('d/m/Y H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        // Registrar la exportación
        AuditLog::log(
            action: 'export',
            description: 'Exportación de logs de auditoría: ' . $logs->count() . ' registros'
        );

        return response()->stream($callback, 200, $headers);
    }
}
