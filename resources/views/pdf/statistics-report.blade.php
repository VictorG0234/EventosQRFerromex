<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Estadísticas - {{ $event->name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #4F46E5;
        }
        
        .header h1 {
            font-size: 24px;
            color: #1F2937;
            margin-bottom: 10px;
        }
        
        .header .event-info {
            font-size: 12px;
            color: #6B7280;
            margin-top: 8px;
        }
        
        .section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #1F2937;
            margin-bottom: 12px;
            padding-bottom: 6px;
            border-bottom: 2px solid #E5E7EB;
        }
        
        .stats-grid {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }
        
        .stats-row {
            display: table-row;
        }
        
        .stat-box {
            display: table-cell;
            width: 25%;
            padding: 12px;
            text-align: center;
            border: 1px solid #E5E7EB;
            background: #F9FAFB;
        }
        
        .stat-box .label {
            font-size: 10px;
            color: #6B7280;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        
        .stat-box .value {
            font-size: 20px;
            font-weight: bold;
            color: #1F2937;
        }
        
        .chart-container {
            margin: 15px 0;
        }
        
        .chart-row {
            display: table;
            width: 100%;
            margin-bottom: 6px;
        }
        
        .chart-label {
            display: table-cell;
            width: 100px;
            padding: 4px 8px;
            font-size: 10px;
            font-weight: 500;
            vertical-align: middle;
        }
        
        .chart-bar-container {
            display: table-cell;
            width: auto;
            vertical-align: middle;
        }
        
        .chart-bar {
            height: 20px;
            background: linear-gradient(to right, #3B82F6, #2563EB);
            color: white;
            padding: 2px 8px;
            font-size: 10px;
            font-weight: bold;
            border-radius: 2px;
            display: inline-block;
            min-width: 30px;
            text-align: right;
        }
        
        .chart-bar-green {
            background: linear-gradient(to right, #10B981, #059669);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        thead {
            background: #F3F4F6;
        }
        
        th {
            padding: 8px;
            text-align: left;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            color: #6B7280;
            border-bottom: 2px solid #E5E7EB;
        }
        
        td {
            padding: 6px 8px;
            border-bottom: 1px solid #F3F4F6;
            font-size: 10px;
        }
        
        tbody tr:hover {
            background: #F9FAFB;
        }
        
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 9px;
            color: #9CA3AF;
            padding: 10px 0;
            border-top: 1px solid #E5E7EB;
        }
        
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de Estadísticas del Evento</h1>
        <div class="event-info">
            <strong>{{ $event->name }}</strong><br>
            Fecha: {{ $event->event_date->format('d/m/Y') }} | Ubicación: {{ $event->location }}<br>
            Generado el: {{ now()->setTimezone('America/Mexico_City')->format('d/m/Y H:i:s') }}
        </div>
    </div>

    <!-- Resumen General -->
    <div class="section">
        <div class="section-title">Resumen General</div>
        <div class="stats-grid">
            <div class="stats-row">
                <div class="stat-box">
                    <div class="label">Total Invitados</div>
                    <div class="value">{{ $statistics['overview']['total_guests'] }}</div>
                </div>
                <div class="stat-box">
                    <div class="label">Asistencias</div>
                    <div class="value">{{ $statistics['overview']['total_attendances'] }}</div>
                </div>
                <div class="stat-box">
                    <div class="label">Pendientes</div>
                    <div class="value">{{ $statistics['overview']['pending_guests'] }}</div>
                </div>
                <div class="stat-box">
                    <div class="label">Tasa Asistencia</div>
                    <div class="value">{{ $statistics['overview']['attendance_rate'] }}%</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Información de Rifas -->
    <div class="section">
        <div class="section-title">Información de Rifas</div>
        <div class="stats-grid">
            <div class="stats-row">
                <div class="stat-box">
                    <div class="label">Total Premios</div>
                    <div class="value">{{ $statistics['overview']['total_prizes'] }}</div>
                </div>
                <div class="stat-box">
                    <div class="label">Stock Total</div>
                    <div class="value">{{ $statistics['overview']['total_prize_stock'] }}</div>
                </div>
                <div class="stat-box">
                    <div class="label">Participantes</div>
                    <div class="value">{{ $statistics['overview']['active_raffle_entries'] }}</div>
                </div>
                <div class="stat-box">
                    <div class="label">-</div>
                    <div class="value">-</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Flujo de Asistencia por Hora -->
    @if(count($statistics['hourly_attendance']) > 0)
    <div class="section">
        <div class="section-title">Flujo de Asistencia por Hora</div>
        <div class="chart-container">
            @php
                $maxCount = max($statistics['hourly_attendance']);
            @endphp
            @foreach($statistics['hourly_attendance'] as $hour => $count)
            <div class="chart-row">
                <div class="chart-label">{{ str_pad($hour, 2, '0', STR_PAD_LEFT) }}:00</div>
                <div class="chart-bar-container">
                    <div class="chart-bar" style="width: {{ $maxCount > 0 ? max(($count / $maxCount) * 300, 30) : 30 }}px;">
                        {{ $count }}
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Asistencia por Área Laboral -->
    @if(count($statistics['attendance_by_work_area']) > 0)
    <div class="section">
        <div class="section-title">Asistencia por Área Laboral</div>
        <div class="chart-container">
            @php
                $maxAreaCount = max($statistics['attendance_by_work_area']);
                $sortedAreas = collect($statistics['attendance_by_work_area'])->sortDesc();
            @endphp
            @foreach($sortedAreas as $area => $count)
            <div class="chart-row">
                <div class="chart-label">{{ Str::limit($area, 15) }}</div>
                <div class="chart-bar-container">
                    <div class="chart-bar chart-bar-green" style="width: {{ $maxAreaCount > 0 ? max(($count / $maxAreaCount) * 300, 30) : 30 }}px;">
                        {{ $count }}
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Salto de página antes de la tabla -->
    <div class="page-break"></div>

    <!-- Registro Completo de Asistencias -->
    @if(count($attendances) > 0)
    <div class="section">
        <div class="section-title">Registro Completo de Asistencias ({{ count($attendances) }} registros)</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 30%;">Nombre</th>
                    <th style="width: 15%;">No. Empleado</th>
                    <th style="width: 30%;">Área</th>
                    <th style="width: 20%;">Hora de Registro</th>
                </tr>
            </thead>
            <tbody>
                @foreach($attendances as $index => $attendance)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $attendance['guest_name'] }}</td>
                    <td>{{ $attendance['employee_number'] }}</td>
                    <td>{{ Str::limit($attendance['work_area'], 25) }}</td>
                    <td>{{ $attendance['attended_at'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="footer">
        Sistema de Gestión de Eventos QR - Ferromex | Página {PAGE_NUM} de {PAGE_COUNT}
    </div>
</body>
</html>
