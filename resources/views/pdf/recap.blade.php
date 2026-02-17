@php $cur = $currencyCode ?? 'AED'; $xr = $exchangeRate ?? 1.0; @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Recap - {{ $estimation->quote_number }}</title>
    <style>
        @include('pdf.partials.styles', ['bodyFontSize' => '11px', 'bodyLineHeight' => '1.4'])

        /* === Recap-specific styles === */
        .section {
            margin: 0 20px 16px;
        }
        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: {{ $headerColor ?? '#1e3a5f' }};
            border-bottom: 2px solid {{ $headerColor ?? '#1e3a5f' }};
            padding-bottom: 4px;
            margin-bottom: 10px;
        }
        td {
            padding: 8px 12px;
            border: 1px solid #e0e0e0;
        }
        .label {
            background-color: #f5f7fa;
            font-weight: bold;
            color: #333;
            width: 50%;
        }
        .value {
            text-align: right;
            width: 50%;
            font-family: 'DejaVu Sans Mono', monospace;
        }
    </style>
</head>
<body>
    @include('pdf.partials.header', ['documentTitle' => 'Estimation Summary (Recap)', 'estimation' => $estimation])

    <div class="section">
        <div class="section-title">Weight Breakdown</div>
        <table>
            <tr class="highlight">
                <td class="label">Total Weight (kg)</td>
                <td class="value">{{ number_format($recapData['total_weight_kg'] ?? 0, 1) }}</td>
            </tr>
            <tr class="highlight">
                <td class="label">Total Weight (MT)</td>
                <td class="value">{{ number_format($recapData['total_weight_mt'] ?? 0, 4) }}</td>
            </tr>
            <tr>
                <td class="label">Steel Weight (kg)</td>
                <td class="value">{{ number_format($recapData['steel_weight_kg'] ?? 0, 1) }}</td>
            </tr>
            <tr>
                <td class="label">Panels Weight (kg)</td>
                <td class="value">{{ number_format($recapData['panels_weight_kg'] ?? 0, 1) }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Price Breakdown</div>
        <table>
            <tr class="highlight">
                <td class="label">Total Price ({{ $cur }})</td>
                <td class="value">{{ number_format(($recapData['total_price_aed'] ?? 0) * $xr, 2) }}</td>
            </tr>
            <tr>
                <td class="label">FOB Price ({{ $cur }})</td>
                <td class="value">{{ number_format(($recapData['fob_price_aed'] ?? 0) * $xr, 2) }}</td>
            </tr>
            <tr class="highlight">
                <td class="label">Price per MT ({{ $cur }}/MT)</td>
                <td class="value">{{ number_format(($recapData['price_per_mt'] ?? 0) * $xr, 2) }}</td>
            </tr>
        </table>
    </div>

    @include('pdf.partials.footer', ['documentTitle' => 'Estimation Summary (Recap)', 'estimation' => $estimation])
</body>
</html>
