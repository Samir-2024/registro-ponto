<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cartão de Ponto - {{ $person->full_name }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 7mm 10mm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        @media print {
            .no-print { display: none !important; }
            body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        }
        
        body { 
            font-family: Arial, Helvetica, sans-serif; 
            font-size: 8.5pt;
            margin: 0;
            padding: 20px;
            width: 100%;
            color: #000;
        }
        
        .header { 
            text-align: center; 
            margin-bottom: 5pt; 
            border-bottom: 1.5pt solid #000; 
            padding-bottom: 4pt;
        }
        
        .header h1 {
            font-size: 14pt;
            margin-bottom: 2pt;
            font-weight: bold;
            letter-spacing: 0.3pt;
        }
        
        .header p {
            font-size: 8.5pt;
            margin: 1pt 0;
            line-height: 1.3;
        }
        
        .info-section { 
            margin: 5pt 0;
            width: 100%;
            font-size: 8.5pt;
            overflow: hidden;
            border: 1pt solid #ccc;
            padding: 5pt;
            background-color: #f9f9f9;
        }
        
        .info-column {
            width: 33.33%;
            float: left;
            box-sizing: border-box;
            padding-right: 8pt;
        }
        
        .info-row { 
            margin-bottom: 2pt;
            line-height: 1.4;
            clear: none;
            min-height: 12pt;
        }
        
        .info-label { 
            font-weight: bold; 
            display: inline;
        }
        
        .info-value {
            display: inline;
            word-wrap: break-word;
            word-break: break-word;
            overflow-wrap: break-word;
            hyphens: auto;
        }
        
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 5pt 0;
            font-size: 7pt;
            table-layout: fixed;
            border: 1pt solid #333;
        }
        
        th { 
            background-color: #e0e0e0; 
            padding: 3pt 1pt;
            text-align: center; 
            font-weight: bold;
            font-size: 7pt;
            white-space: nowrap;
            line-height: 1.2;
            border-bottom: 1pt solid #333;
        }
        
        td { 
            padding: 3pt 1pt;
            text-align: center;
            font-size: 7pt;
            line-height: 1.2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            border-bottom: 1pt solid #333;
        }
        
        .day-col { 
            text-align: left; 
            padding-left: 4pt;
            font-size: 7pt;
            width: 55pt;
        }
        
        .time-col { 
            font-size: 7pt;
            width: 28pt;
        }
        
        .hours-col { 
            font-size: 7pt;
            white-space: nowrap;
            width: 32pt;
        }
        
        /* Bordas verticais seletivas */
        .border-right {
            border-right: 1pt solid #333;
        }
        
        .total-row { 
            font-weight: bold; 
            background-color: #f9f9f9;
        }
        
        .observations {
            margin-top: 5pt;
            page-break-inside: avoid;
            width: 100%;
        }
        
        .observations p {
            font-size: 8.5pt;
            font-weight: bold;
            margin-bottom: 2pt;
        }
        
        .observations-box {
            border: 1pt solid #333;
            min-height: 40pt;
            padding: 4pt;
            font-size: 8.5pt;
            width: 100%;
            box-sizing: border-box;
            background-color: #fafafa;
        }
        
        /* Estilos compactos para jornadas flexíveis */
        .observations.compact {
            margin-top: 3pt;
        }
        
        .observations-box.compact {
            min-height: 32pt;
            padding: 3pt;
            font-size: 7.5pt;
        }
        
        .signature-section { 
            margin-top: 24pt;
            width: 100%;
            page-break-inside: avoid;
            overflow: hidden;
        }
        
        .signature-section.compact {
            margin-top: 24pt;
        }
        
        .signature-box { 
            width: 48%; 
            float: left;
            text-align: center;
            box-sizing: border-box;
        }
        
        .signature-box:first-child {
            margin-right: 4%;
        }
        
        .signature-line { 
            border-top: 1pt solid #000; 
            margin-top: 18pt;
            padding-top: 3pt;
            font-size: 8.5pt;
            font-weight: normal;
        }
        
        .signature-line.compact {
            margin-top: 12pt;
            padding-top: 2pt;
            font-size: 7.5pt;
        }
        
        .flexible-summary {
            margin-top: 3pt;
            border: 1pt solid #333;
            padding: 4pt;
            background-color: #f0f8ff;
            page-break-inside: avoid;
        }
        
        .flexible-summary-title {
            font-weight: bold;
            margin-bottom: 3pt;
            font-size: 8pt;
            text-align: center;
        }
        
        .flexible-summary-content {
            display: flex;
            justify-content: space-between;
            font-size: 7.5pt;
            gap: 8pt;
        }
        
        .flexible-summary-item {
            flex: 1;
            text-align: center;
        }
        
        .flexible-summary-status {
            margin-top: 2pt;
            font-size: 7pt;
            text-align: center;
            font-style: italic;
        }
        
        .footer {
            margin-top: 5pt;
            text-align: center;
            font-size: 7.5pt;
            page-break-inside: avoid;
            clear: both;
            color: #666;
        }
        
        .footer p {
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" style="background: #3b82f6; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">Imprimir</button>
        <a href="{{ route('timesheets.index') }}" style="background: #6b7280; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; margin-left: 10px;">Voltar</a>
    </div>

    <div class="header">
        <h1>CARTÃO DE PONTO</h1>
        <p>{{ $establishment->corporate_name }}</p>
        <p>CNPJ: {{ $establishment->cnpj }}</p>
    </div>

    <div class="info-section">
        <div class="info-column">
            <div class="info-row">
                <span class="info-label">Nome:</span>
                <span class="info-value">{{ $person->full_name }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">PIS/PASEP:</span>
                <span class="info-value">{{ $person->pis_pasep_formatted ?? '-' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Função:</span>
                <span class="info-value">{{ $registration->position }}</span>
            </div>
        </div>
        <div class="info-column">
            <div class="info-row">
                <span class="info-label">CPF:</span>
                <span class="info-value">{{ $person->cpf_formatted }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">CTPS:</span>
                <span class="info-value">{{ $person->ctps ?? '-' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Departamento:</span>
                <span class="info-value">{{ $registration->department->name ?? 'N/A' }}</span>
            </div>
        </div>
        <div class="info-column">
            <div class="info-row">
                <span class="info-label">Matrícula:</span>
                <span class="info-value">{{ $registration->matricula ?? '-' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Admissão:</span>
                <span class="info-value">{{ \Carbon\Carbon::parse($registration->admission_date)->format('d/m/Y') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Período:</span>
                <span class="info-value">{{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} a {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</span>
            </div>
        </div>
        <div style="clear: both;"></div>
    </div>

    <table>
        <thead>
            <tr>
                <th class="day-col border-right">Dia</th>
                <th class="time-col">E1</th>
                <th class="time-col">S1</th>
                <th class="time-col">E2</th>
                <th class="time-col">S2</th>
                <th class="time-col">E3</th>
                <th class="time-col">S3</th>
                <th class="time-col">E4</th>
                <th class="time-col border-right">S4</th>
                <th class="hours-col border-right">H.Extra</th>
                <th class="hours-col">Faltas</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalOvertimeMinutes = 0;
                $totalAbsenceMinutes = 0;
                $daysOfWeek = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
            @endphp

            @foreach($dailyRecords as $date => $records)
                @php
                    $carbonDate = \Carbon\Carbon::parse($date);
                    $dayOfWeek = $daysOfWeek[$carbonDate->dayOfWeek];
                    // CORRIGIDO: usar 'recorded_at' em vez de 'record_time'
                    $punches = $records->sortBy('recorded_at')->pluck('record_time')->map(function($time) {
                        return substr($time, 0, 5);
                    })->toArray();
                    // Valores já vêm em MINUTOS do Service
                    $overtime = $calculations[$date]['overtime'] ?? 0;
                    $absence = $calculations[$date]['absence'] ?? 0;
                    $totalOvertimeMinutes += $overtime;
                    $totalAbsenceMinutes += $absence;
                @endphp
                
                <tr>
                    <td class="day-col border-right">
                        {{ $carbonDate->format('d/m/y') }} ({{ $dayOfWeek }})
                        @if(!empty($calculations[$date]['inconsistency']))
                            <span title="Inconsistência: batida ímpar. Última batida não pareada: {{ $calculations[$date]['incomplete_last'] ?? 'N/A' }}" style="color: #d9534f; font-weight: bold; cursor: help;">*</span>
                        @endif
                    </td>
                    <td class="time-col">{{ $punches[0] ?? '' }}</td>
                    <td class="time-col">{{ $punches[1] ?? '' }}</td>
                    <td class="time-col">{{ $punches[2] ?? '' }}</td>
                    <td class="time-col">{{ $punches[3] ?? '' }}</td>
                    <td class="time-col">{{ $punches[4] ?? '' }}</td>
                    <td class="time-col">{{ $punches[5] ?? '' }}</td>
                    <td class="time-col">{{ $punches[6] ?? '' }}</td>
                    <td class="time-col border-right">{{ $punches[7] ?? '' }}</td>
                    {{-- CORRIGIDO: valores já estão em minutos, não multiplicar por 60 --}}
                    <td class="hours-col border-right">{{ $overtime > 0 ? gmdate('H:i', $overtime * 60) : '' }}</td>
                    <td class="hours-col">{{ $absence > 0 ? gmdate('H:i', $absence * 60) : '' }}</td>
                </tr>
            @endforeach

            <tr class="total-row">
                <td colspan="9" style="text-align: right; padding-right: 10px;" class="border-right"><strong>TOTAIS:</strong></td>
                {{-- CORRIGIDO: valores já estão em minutos, não multiplicar por 60 --}}
                <td class="hours-col border-right"><strong>{{ gmdate('H:i', $totalOvertimeMinutes * 60) }}</strong></td>
                <td class="hours-col"><strong>{{ gmdate('H:i', $totalAbsenceMinutes * 60) }}</strong></td>
            </tr>
        </tbody>
    </table>

    @php
        $isFlexible = isset($is_flexible_hours) && $is_flexible_hours;
        $isRotating = isset($is_rotating_shift) && $is_rotating_shift;
        $isCompact = $isFlexible || $isRotating;
    @endphp

    @if($isFlexible && isset($flexible_summary))
    <div class="flexible-summary">
        <div class="flexible-summary-title">RESUMO - JORNADA DE HORAS FLEXÍVEIS</div>
        <div class="flexible-summary-content">
            <div class="flexible-summary-item">
                <strong>Período:</strong> {{ $flexible_summary['period']['type'] === 'weekly' ? 'Semanal' : ($flexible_summary['period']['type'] === 'monthly' ? 'Mensal' : 'Quinzenal') }}
            </div>
            <div class="flexible-summary-item">
                <strong>Devidas:</strong> {{ number_format($flexible_summary['hours']['required'], 2) }}h
            </div>
            <div class="flexible-summary-item">
                <strong>Trabalhadas:</strong> {{ number_format($flexible_summary['hours']['worked'], 2) }}h
            </div>
            <div class="flexible-summary-item">
                <strong>Saldo:</strong> 
                <span style="color: {{ $flexible_summary['hours']['balance'] >= 0 ? '#059669' : '#dc2626' }}; font-weight: bold;">
                    {{ $flexible_summary['hours']['balance'] >= 0 ? '+' : '' }}{{ number_format($flexible_summary['hours']['balance'], 2) }}h
                </span>
            </div>
        </div>
        <div class="flexible-summary-status">
            Status: <strong>{{ $flexible_summary['status_text'] }}</strong>@if(count($flexible_summary['violations']) > 0) <span style="color: #dc2626;">({{ count($flexible_summary['violations']) }} irregularidade(s))</span>@endif
        </div>
    </div>
    @endif

    @if($isRotating && isset($rotating_summary))
    <div class="flexible-summary">
        <div class="flexible-summary-title">RESUMO - ESCALA DE REVEZAMENTO {{ $rotating_summary['cycle_info']['cycle_name'] }}</div>
        <div class="flexible-summary-content">
            <div class="flexible-summary-item">
                <strong>Ciclo:</strong> {{ $rotating_summary['cycle_info']['work_days'] }} dia(s) ON / {{ $rotating_summary['cycle_info']['rest_days'] }} dia(s) OFF
            </div>
            <div class="flexible-summary-item">
                <strong>Dias no Período:</strong> {{ $rotating_summary['period_stats']['work_days_in_period'] }} trabalho / {{ $rotating_summary['period_stats']['rest_days_in_period'] }} folga
            </div>
            <div class="flexible-summary-item">
                <strong>Presença:</strong> {{ $rotating_summary['period_stats']['days_with_presence'] }}/{{ $rotating_summary['period_stats']['work_days_in_period'] }} dias
            </div>
            <div class="flexible-summary-item">
                <strong>Duração Plantão:</strong> {{ number_format($rotating_summary['cycle_info']['shift_duration'], 0) }}h
            </div>
        </div>
        @php
            $formatMinutes = function($minutes) {
                $hours = floor($minutes / 60);
                $mins = $minutes % 60;
                return sprintf('%02d:%02d', $hours, $mins);
            };
        @endphp
        <div class="flexible-summary-status">
            Total Esperado: <strong>{{ $formatMinutes($rotating_summary['hours']['expected_total']) }}</strong> | 
            Trabalhado: <strong>{{ $formatMinutes($rotating_summary['hours']['worked_total']) }}</strong>
        </div>
    </div>
    @endif

    <div class="observations {{ $isCompact ? 'compact' : '' }}">
        <p>Observações:</p>
        <div class="observations-box {{ $isCompact ? 'compact' : '' }}"></div>
    </div>

    <div class="signature-section {{ $isCompact ? 'compact' : '' }}">
        <div class="signature-box">
            <div class="signature-line {{ $isCompact ? 'compact' : '' }}">Assinatura do Colaborador</div>
        </div>
        <div class="signature-box">
            <div class="signature-line {{ $isCompact ? 'compact' : '' }}">Assinatura do Empregador</div>
        </div>
        <div style="clear: both;"></div>
    </div>

    <div class="footer">
        <p>Emitido: {{ now()->format('d/m/Y H:i') }}</p>
    </div>
</body>
</html>
