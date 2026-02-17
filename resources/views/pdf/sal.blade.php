@php $cur = $currencyCode ?? 'AED'; $xr = $exchangeRate ?? 1.0; @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SAL - {{ $estimation->quote_number }}</title>
    <style>
        @include('pdf.partials.styles', ['bodyFontSize' => '11px', 'bodyLineHeight' => '1.4'])

        /* === SAL-specific styles === */
        th {
            background-color: {{ $headerColor ?? '#1e3a5f' }};
            color: #ffffff;
            font-weight: bold;
            text-align: center;
            padding: 8px 6px;
            font-size: 10px;
            border: 1px solid {{ $headerColor ?? '#1e3a5f' }};
        }
        td {
            padding: 6px 8px;
            border: 1px solid #ccc;
            font-size: 10px;
        }
        .total-row td {
            font-size: 11px;
        }
    </style>
</head>
<body>
    @include('pdf.partials.header', ['documentTitle' => 'Sales Analysis (SAL)', 'estimation' => $estimation])

    <table>
        <thead>
            <tr>
                <th style="width: 6%;">Code</th>
                <th style="width: 32%;">Description</th>
                <th style="width: 12%;">Weight (kg)</th>
                <th style="width: 14%;">Cost ({{ $cur }})</th>
                <th style="width: 8%;">Markup</th>
                <th style="width: 14%;">Price ({{ $cur }})</th>
                <th style="width: 14%;">{{ $cur }}/MT</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($salData['lines'] ?? [] as $line)
                @if (($line['weight_kg'] ?? 0) == 0 && ($line['cost'] ?? 0) == 0 && ($line['price'] ?? 0) == 0)
                    @continue
                @endif
                <tr>
                    <td class="center">{{ $line['code'] }}</td>
                    <td>{{ $line['description'] }}</td>
                    <td class="number">{{ number_format($line['weight_kg'] ?? 0, 3) }}</td>
                    <td class="number">{{ number_format(($line['cost'] ?? 0) * $xr, 2) }}</td>
                    <td class="number">{{ number_format($line['markup'] ?? 0, 3) }}</td>
                    <td class="number">{{ number_format(($line['price'] ?? 0) * $xr, 2) }}</td>
                    <td class="number">{{ number_format(($line['price_per_mt'] ?? 0) * $xr, 2) }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td class="center"></td>
                <td>TOTAL</td>
                <td class="number">{{ number_format($salData['total_weight_kg'] ?? 0, 3) }}</td>
                <td class="number">{{ number_format(($salData['total_cost'] ?? 0) * $xr, 2) }}</td>
                <td class="number">{{ number_format($salData['markup_ratio'] ?? 0, 3) }}</td>
                <td class="number">{{ number_format(($salData['total_price'] ?? 0) * $xr, 2) }}</td>
                <td class="number">{{ number_format(($salData['price_per_mt'] ?? 0) * $xr, 2) }}</td>
            </tr>
        </tbody>
    </table>

    @include('pdf.partials.footer', ['documentTitle' => 'Sales Analysis (SAL)', 'estimation' => $estimation])
</body>
</html>
