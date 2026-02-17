@php $cur = $currencyCode ?? 'AED'; $xr = $exchangeRate ?? 1.0; @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BOQ - {{ $estimation->quote_number }}</title>
    <style>
        @include('pdf.partials.styles', ['bodyFontSize' => '11px', 'bodyLineHeight' => '1.4'])

        /* === BOQ-specific styles === */
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
    @include('pdf.partials.header', ['documentTitle' => 'Bill of Quantities (BOQ)', 'estimation' => $estimation])

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">SL No</th>
                <th style="width: 45%;">Item Description</th>
                <th style="width: 8%;">Unit</th>
                <th style="width: 12%;">QTY</th>
                <th style="width: 15%;">Unit Rate ({{ $cur }})</th>
                <th style="width: 15%;">Total Price ({{ $cur }})</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($boqData['items'] as $item)
                <tr>
                    <td class="center">{{ $item['sl_no'] }}</td>
                    <td>{{ $item['description'] }}</td>
                    <td class="center">{{ $item['unit'] }}</td>
                    <td class="number">{{ number_format($item['quantity'], 4) }}</td>
                    <td class="number">{{ number_format($item['unit_rate'] * $xr, 2) }}</td>
                    <td class="number">{{ number_format($item['total_price'] * $xr, 2) }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="2" style="text-align: right;">Total</td>
                <td class="center">MT</td>
                <td class="number">{{ number_format($boqData['total_weight_mt'], 4) }}</td>
                <td></td>
                <td class="number">{{ number_format($boqData['total_price'] * $xr, 2) }}</td>
            </tr>
        </tbody>
    </table>

    @include('pdf.partials.footer', ['documentTitle' => 'Bill of Quantities (BOQ)', 'estimation' => $estimation])
</body>
</html>
