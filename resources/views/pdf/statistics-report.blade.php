<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Estadísticas - {{ $event->name }}</title>
    <style>
        @page {
            margin: 100px 50px 80px 50px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            line-height: 1.6;
            color: #2D3748;
            background: #ffffff;
        }
        
        /* Header fijo en todas las páginas */
        .page-header {
            position: fixed;
            top: -80px;
            left: 0;
            right: 0;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 50px;
            text-align: center;
        }
        
        .page-header h1 {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 5px;
            letter-spacing: 0.5px;
        }
        
        .page-header .subtitle {
            font-size: 11px;
            opacity: 0.95;
        }
        
        /* Footer fijo en todas las páginas */
        .page-footer {
            position: fixed;
            bottom: -60px;
            left: 0;
            right: 0;
            height: 60px;
            background: #F7FAFC;
            border-top: 3px solid #667eea;
            padding: 15px 50px;
            font-size: 9px;
            color: #718096;
        }
        
        .page-footer .footer-content {
            display: table;
            width: 100%;
        }
        
        .page-footer .footer-left,
        .page-footer .footer-right {
            display: table-cell;
            width: 50%;
        }
        
        .page-footer .footer-right {
            text-align: right;
        }
        
        /* Contenido principal */
        .content {
            padding: 20px 30px;
        }
        
        .event-info-box {
            background: #EBF4FF;
            border-left: 4px solid #4299E1;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 4px;
        }
        
        .event-info-box strong {
            color: #2C5282;
            font-size: 13px;
            display: block;
            margin-bottom: 8px;
        }
        
        .event-info-box .info-row {
            margin: 4px 0;
            color: #2D3748;
            font-size: 10px;
        }
        
        .section {
            margin-bottom: 35px;
            page-break-inside: avoid;
            padding: 0 10px;
        }
        
        .section-title {
            font-size: 15px;
            font-weight: bold;
            color: #2D3748;
            margin-bottom: 18px;
            padding-bottom: 10px;
            border-bottom: 3px solid #667eea;
            display: block;
        }
        
        /* Tarjetas de estadísticas mejoradas */
        .stats-container {
            width: 100%;
            margin-bottom: 25px;
        }
        
        .stats-row {
            width: 100%;
            clear: both;
        }
        
        .stat-card {
            float: left;
            width: 22%;
            margin-right: 4%;
            padding: 22px 15px;
            text-align: center;
            border-radius: 8px;
            box-shadow: 0 3px 6px rgba(0,0,0,0.15);
            position: relative;
            overflow: hidden;
        }
        
        .stat-card:last-child {
            margin-right: 0;
        }
        
        .stat-card.blue {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .stat-card.green {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
        }
        
        .stat-card.orange {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }
        
        .stat-card.purple {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }
        
        .stat-card .label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            opacity: 0.9;
            font-weight: 600;
        }
        
        .stat-card .value {
            font-size: 32px;
            font-weight: bold;
            line-height: 1;
            margin-bottom: 5px;
        }
        
        .stat-card .unit {
            font-size: 9px;
            opacity: 0.85;
        }
        
        /* Gráficos de barras mejorados */
        .chart-container {
            margin: 20px 0;
            background: #F7FAFC;
            padding: 20px;
            border-radius: 8px;
        }
        
        .chart-row {
            width: 100%;
            clear: both;
            margin-bottom: 12px;
            min-height: 30px;
        }
        
        .chart-label {
            float: left;
            width: 140px;
            padding: 8px 10px;
            font-size: 10px;
            font-weight: 600;
            color: #4A5568;
            line-height: 24px;
        }
        
        .chart-bar-container {
            margin-left: 150px;
            padding: 4px 0;
        }
        
        .chart-bar {
            height: 28px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 6px 12px;
            font-size: 10px;
            font-weight: bold;
            border-radius: 4px;
            display: block;
            min-width: 50px;
            text-align: right;
            line-height: 16px;
            box-shadow: 0 2px 4px rgba(102, 126, 234, 0.3);
        }
        
        .chart-bar-green {
            background: linear-gradient(90deg, #11998e 0%, #38ef7d 100%);
            box-shadow: 0 2px 4px rgba(17, 153, 142, 0.3);
        }
        
        /* Gráfico circular (simulado con CSS) */
        .donut-chart {
            text-align: center;
            margin: 20px 0;
        }
        
        .donut-chart svg {
            transform: rotate(-90deg);
        }
        
        .donut-segment {
            fill: none;
            stroke-width: 25;
        }
        
        /* Tabla mejorada */
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 20px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        th {
            padding: 14px 12px;
            text-align: left;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        tbody tr {
            background: white;
        }
        
        tbody tr:nth-child(even) {
            background: #F7FAFC;
        }
        
        td {
            padding: 12px;
            border-bottom: 1px solid #E2E8F0;
            font-size: 10px;
            color: #4A5568;
        }
        
        tbody tr:last-child td {
            border-bottom: none;
        }
        
        .page-break {
            page-break-after: always;
        }
        
        /* Badge de resumen */
        .summary-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 14px;
            font-size: 10px;
            font-weight: bold;
            margin-right: 10px;
            margin-bottom: 8px;
        }
        
        .summary-badge.success {
            background: #C6F6D5;
            color: #22543D;
        }
        
        .summary-badge.warning {
            background: #FEEBC8;
            color: #744210;
        }
        
        .summary-badge.info {
            background: #BEE3F8;
            color: #2C5282;
        }
        
        /* Grid de información */
        .info-grid {
            width: 100%;
            margin: 20px 0;
        }
        
        .info-row {
            width: 100%;
            clear: both;
            margin-bottom: 10px;
        }
        
        .info-cell {
            float: left;
            width: 48%;
            margin-right: 4%;
            padding: 15px 12px;
            border: 2px solid #E2E8F0;
            background: #F7FAFC;
            box-sizing: border-box;
            border-radius: 6px;
        }
        
        .info-cell:nth-child(2n) {
            margin-right: 0;
        }
        
        .info-cell strong {
            color: #2D3748;
            font-size: 9px;
            display: block;
            margin-bottom: 6px;
            letter-spacing: 0.5px;
        }
        
        .info-cell span {
            color: #4A5568;
            font-size: 13px;
            font-weight: 600;
        }
        
        /* Clearfix */
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
    </style>
</head>
<body>
    <!-- Header fijo -->
    <div class="page-header">
        <h1>REPORTE DE ESTADÍSTICAS DEL EVENTO</h1>
        <div class="subtitle">Sistema de Gestión de Eventos QR - Ferromex</div>
    </div>

    <!-- Footer fijo -->
    <div class="page-footer">
        <div class="footer-content">
            <div class="footer-left">
                Generado el: {{ now()->setTimezone('America/Mexico_City')->format('d/m/Y H:i:s') }}
            </div>
            <div class="footer-right">
                Página <script type="text/php">
                    if (isset($pdf)) {
                        $font = $fontMetrics->getFont("DejaVu Sans");
                        $pdf->page_text(520, 810, "{PAGE_NUM} de {PAGE_COUNT}", $font, 9, array(0.45, 0.52, 0.56));
                    }
                </script>
            </div>
        </div>
    </div>

    <!-- Contenido principal -->
    <div class="content">
        <!-- Información del evento -->
        <div class="event-info-box">
            <strong>{{ $event->name }}</strong>
            <div class="info-row">
                <span style="display: inline-block; width: 15px;">📅</span> 
                <strong>Fecha:</strong> {{ $event->event_date->format('d/m/Y') }}
                <span style="margin-left: 20px; display: inline-block; width: 15px;">📍</span> 
                <strong>Ubicación:</strong> {{ $event->location }}
            </div>
            @if($event->description)
            <div class="info-row" style="margin-top: 8px; font-style: italic;">
                {{ Str::limit($event->description, 150) }}
            </div>
            @endif
        </div>

        <!-- Resumen General con tarjetas mejoradas -->
        <div class="section">
            <div class="section-title">Resumen General del Evento</div>
            
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                <tr>
                    <td style="width: 25%; padding: 18px; text-align: center; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 8px; vertical-align: top;">
                        <div style="font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; opacity: 0.9; font-weight: 600;">TOTAL INVITADOS</div>
                        <div style="font-size: 32px; font-weight: bold; line-height: 1; margin-bottom: 5px;">{{ number_format($statistics['overview']['total_guests']) }}</div>
                        <div style="font-size: 9px; opacity: 0.85;">Registrados</div>
                    </td>
                    <td style="width: 3%;"></td>
                    <td style="width: 25%; padding: 18px; text-align: center; background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; border-radius: 8px; vertical-align: top;">
                        <div style="font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; opacity: 0.9; font-weight: 600;">ASISTENCIAS</div>
                        <div style="font-size: 32px; font-weight: bold; line-height: 1; margin-bottom: 5px;">{{ number_format($statistics['overview']['total_attendances']) }}</div>
                        <div style="font-size: 9px; opacity: 0.85;">Confirmadas</div>
                    </td>
                    <td style="width: 3%;"></td>
                    <td style="width: 25%; padding: 18px; text-align: center; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; border-radius: 8px; vertical-align: top;">
                        <div style="font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; opacity: 0.9; font-weight: 600;">PENDIENTES</div>
                        <div style="font-size: 32px; font-weight: bold; line-height: 1; margin-bottom: 5px;">{{ number_format($statistics['overview']['pending_guests']) }}</div>
                        <div style="font-size: 9px; opacity: 0.85;">Sin registrar</div>
                    </td>
                    <td style="width: 3%;"></td>
                    <td style="width: 25%; padding: 18px; text-align: center; background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; border-radius: 8px; vertical-align: top;">
                        <div style="font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; opacity: 0.9; font-weight: 600;">TASA ASISTENCIA</div>
                        <div style="font-size: 32px; font-weight: bold; line-height: 1; margin-bottom: 5px;">{{ $statistics['overview']['attendance_rate'] }}%</div>
                        <div style="font-size: 9px; opacity: 0.85;">Del total</div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Información de Rifas -->
        <div class="section">
            <div class="section-title">Información de Rifas y Premios</div>
            
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="width: 48%; padding: 15px 12px; border: 2px solid #E2E8F0; background: #F7FAFC; border-radius: 6px; vertical-align: top;">
                        <div style="font-size: 9px; color: #2D3748; margin-bottom: 6px; letter-spacing: 0.5px; font-weight: bold;">TOTAL DE PREMIOS</div>
                        <div style="font-size: 13px; color: #4A5568; font-weight: 600;">{{ number_format($statistics['overview']['total_prizes']) }}</div>
                    </td>
                    <td style="width: 4%;"></td>
                    <td style="width: 48%; padding: 15px 12px; border: 2px solid #E2E8F0; background: #F7FAFC; border-radius: 6px; vertical-align: top;">
                        <div style="font-size: 9px; color: #2D3748; margin-bottom: 6px; letter-spacing: 0.5px; font-weight: bold;">STOCK TOTAL DISPONIBLE</div>
                        <div style="font-size: 13px; color: #4A5568; font-weight: 600;">{{ number_format($statistics['overview']['total_prize_stock']) }}</div>
                    </td>
                </tr>
                <tr><td colspan="3" style="height: 10px;"></td></tr>
                <tr>
                    <td style="width: 48%; padding: 15px 12px; border: 2px solid #E2E8F0; background: #F7FAFC; border-radius: 6px; vertical-align: top;">
                        <div style="font-size: 9px; color: #2D3748; margin-bottom: 6px; letter-spacing: 0.5px; font-weight: bold;">PARTICIPANTES EN RIFAS</div>
                        <div style="font-size: 13px; color: #4A5568; font-weight: 600;">{{ number_format($statistics['overview']['active_raffle_entries']) }}</div>
                    </td>
                    <td style="width: 4%;"></td>
                    <td style="width: 48%; padding: 15px 12px; border: 2px solid #E2E8F0; background: #F7FAFC; border-radius: 6px; vertical-align: top;">
                        <div style="font-size: 9px; color: #2D3748; margin-bottom: 6px; letter-spacing: 0.5px; font-weight: bold;">TASA DE PARTICIPACIÓN</div>
                        <div style="font-size: 13px; color: #4A5568; font-weight: 600;">
                            @if($statistics['overview']['total_attendances'] > 0)
                                {{ round(($statistics['overview']['active_raffle_entries'] / $statistics['overview']['total_attendances']) * 100, 1) }}%
                            @else
                                0%
                            @endif
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Flujo de Asistencia por Hora -->
        @if(count($statistics['hourly_attendance']) > 0)
        <div class="section">
            <div class="section-title">Flujo de Asistencia por Hora</div>
            
            @php
                $maxCount = max($statistics['hourly_attendance']);
                $totalHourly = array_sum($statistics['hourly_attendance']);
            @endphp
            
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 12px;">
                @foreach($statistics['hourly_attendance'] as $hour => $count)
                <tr style="height: 38px;">
                    <td style="width: 80px; padding: 8px 10px; font-size: 10px; color: #4A5568; font-weight: 600; vertical-align: middle;">
                        {{ str_pad($hour, 2, '0', STR_PAD_LEFT) }}:00
                    </td>
                    <td style="padding: 8px 5px; vertical-align: middle;">
                        @php
                            $barWidth = $maxCount > 0 ? max(($count / $maxCount) * 380, 50) : 50;
                        @endphp
                        <div style="background: linear-gradient(90deg, #667eea 0%, #764ba2 100%); color: white; padding: 8px 12px; border-radius: 4px; font-size: 10px; font-weight: 600; width: {{ $barWidth }}px; text-align: center;">
                            {{ $count }} ({{ $totalHourly > 0 ? round(($count / $totalHourly) * 100, 1) : 0 }}%)
                        </div>
                    </td>
                </tr>
                @endforeach
            </table>
            
            <div style="text-align: center; margin-top: 10px; font-size: 9px; color: #718096;">
                <span class="summary-badge info">Hora pico: {{ str_pad(array_search($maxCount, $statistics['hourly_attendance']), 2, '0', STR_PAD_LEFT) }}:00 con {{ $maxCount }} asistencias</span>
            </div>
        </div>
        @endif

        <!-- Asistencia por Área Laboral -->
        @if(count($statistics['attendance_by_work_area']) > 0)
        <div class="section">
            <div class="section-title">Asistencia por Área Laboral (Top 10)</div>
            
            @php
                $maxAreaCount = max($statistics['attendance_by_work_area']);
                $sortedAreas = collect($statistics['attendance_by_work_area'])->sortDesc()->take(10);
                $totalAreas = array_sum($statistics['attendance_by_work_area']);
            @endphp
            
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 12px;">
                @foreach($sortedAreas as $area => $count)
                <tr style="height: 38px;">
                    <td style="width: 150px; padding: 8px 10px; font-size: 10px; color: #4A5568; font-weight: 600; vertical-align: middle;" title="{{ $area }}">
                        {{ Str::limit($area, 20) }}
                    </td>
                    <td style="padding: 8px 5px; vertical-align: middle;">
                        @php
                            $barWidth = $maxAreaCount > 0 ? max(($count / $maxAreaCount) * 380, 50) : 50;
                        @endphp
                        <div style="background: linear-gradient(90deg, #48bb78 0%, #38a169 100%); color: white; padding: 8px 12px; border-radius: 4px; font-size: 10px; font-weight: 600; width: {{ $barWidth }}px; text-align: center;">
                            {{ $count }} ({{ $totalAreas > 0 ? round(($count / $totalAreas) * 100, 1) : 0 }}%)
                        </div>
                    </td>
                </tr>
                @endforeach
            </table>
            
            <div style="text-align: center; margin-top: 10px; font-size: 9px; color: #718096;">
                <span class="summary-badge success">Total de áreas: {{ count($statistics['attendance_by_work_area']) }}</span>
                <span class="summary-badge info">Área con más asistencia: {{ Str::limit(array_search($maxAreaCount, $statistics['attendance_by_work_area']), 25) }}</span>
            </div>
        </div>
        @endif

        <!-- Salto de página -->
        <div class="page-break"></div>

        <!-- Registro Completo de Asistencias -->
        @if(count($attendances) > 0)
        <div class="section">
            <div class="section-title">Registro Completo de Asistencias</div>
            
            <div style="margin-bottom: 15px; text-align: center;">
                <span class="summary-badge success">{{ count($attendances) }} asistencias registradas</span>
                @if(count($attendances) > 0)
                    <span class="summary-badge info">Primera: {{ $attendances[count($attendances) - 1]['attended_at'] ?? 'N/A' }}</span>
                    <span class="summary-badge warning">Última: {{ $attendances[0]['attended_at'] ?? 'N/A' }}</span>
                @endif
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 30%;">Nombre Completo</th>
                        <th style="width: 12%;">No. Empleado</th>
                        <th style="width: 28%;">Área Laboral</th>
                        <th style="width: 25%;">Fecha y Hora de Registro</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($attendances as $index => $attendance)
                    <tr>
                        <td style="text-align: center; font-weight: bold; color: #667eea;">{{ $index + 1 }}</td>
                        <td style="font-weight: 600; color: #2D3748;">{{ $attendance['guest_name'] }}</td>
                        <td style="text-align: center;">{{ $attendance['employee_number'] }}</td>
                        <td>{{ Str::limit($attendance['work_area'], 30) }}</td>
                        <td style="text-align: center; font-family: monospace;">{{ $attendance['attended_at'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <!-- Resumen final -->
        <div class="section" style="margin-top: 35px; padding: 20px; background: #F7FAFC; border-left: 4px solid #667eea; border-radius: 4px;">
            <div style="font-size: 12px; color: #2D3748; font-weight: 600; margin-bottom: 10px;">
                📊 Resumen Ejecutivo
            </div>
            <div style="font-size: 10px; color: #4A5568; line-height: 1.8;">
                Este evento contó con <strong>{{ number_format($statistics['overview']['total_guests']) }} invitados</strong> registrados, 
                de los cuales <strong>{{ number_format($statistics['overview']['total_attendances']) }} confirmaron su asistencia</strong>, 
                representando una <strong>tasa de asistencia del {{ $statistics['overview']['attendance_rate'] }}%</strong>.
                
                @if(count($statistics['hourly_attendance']) > 0)
                    La hora con mayor flujo fue <strong>{{ str_pad(array_search(max($statistics['hourly_attendance']), $statistics['hourly_attendance']), 2, '0', STR_PAD_LEFT) }}:00</strong> 
                    con <strong>{{ max($statistics['hourly_attendance']) }} asistencias</strong>.
                @endif
                
                @if(count($statistics['attendance_by_work_area']) > 0)
                    El área con mayor participación fue <strong>{{ Str::limit(array_search(max($statistics['attendance_by_work_area']), $statistics['attendance_by_work_area']), 40) }}</strong> 
                    con <strong>{{ max($statistics['attendance_by_work_area']) }} asistentes</strong>.
                @endif
            </div>
        </div>
    </div>
</body>
</html>
