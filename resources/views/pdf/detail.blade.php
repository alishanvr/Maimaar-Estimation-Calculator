<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Detail - {{ $estimation->quote_number }}</title>
    <style>
        @include('pdf.partials.styles', ['bodyFontSize' => '9px', 'bodyLineHeight' => '1.3'])

        /* === Detail-specific styles === */
        th {
            background-color: {{ $headerColor ?? '#1e3a5f' }};
            color: #ffffff;
            font-weight: bold;
            text-align: center;
            padding: 5px 4px;
            font-size: 8px;
            border: 1px solid {{ $headerColor ?? '#1e3a5f' }};
        }
        td {
            padding: 3px 4px;
            border: 1px solid #ccc;
            font-size: 8px;
        }
        .header-row td {
            font-weight: bold;
            background-color: #e2e8f0;
            color: {{ $headerColor ?? '#1e3a5f' }};
            font-size: 9px;
            border-top: 2px solid {{ $headerColor ?? '#1e3a5f' }};
        }
    </style>
</head>
<body>
    @include('pdf.partials.header', ['documentTitle' => 'Detail Sheet', 'estimation' => $estimation])

    <table>
        <thead>
            <tr>
                <th style="width: 22%;">Description</th>
                <th style="width: 6%;">Code</th>
                <th style="width: 5%;">Sales</th>
                <th style="width: 7%;">Cost Code</th>
                <th style="width: 7%;">Size</th>
                <th style="width: 5%;">Qty</th>
                <th style="width: 5%;">Unit</th>
                <th style="width: 8%;">Wt/Unit</th>
                <th style="width: 8%;">Rate</th>
                <th style="width: 12%;">Total Weight</th>
                <th style="width: 15%;">Total Cost</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($detailData as $item)
                @if ($item['is_header'] ?? false)
                    <tr class="header-row">
                        <td colspan="11">{{ $item['description'] }}</td>
                    </tr>
                @else
                    @php
                        $size = is_numeric($item['size'] ?? 0) ? (float) $item['size'] : 0;
                        $qty = (float) ($item['qty'] ?? 0);
                        $wpu = (float) ($item['weight_per_unit'] ?? 0);
                        $rate = (float) ($item['rate'] ?? 0);
                        $totalWeight = $wpu * $size * $qty;
                        $totalCost = $rate * $size * $qty;
                    @endphp
                    <tr>
                        <td>{{ $item['description'] }}</td>
                        <td class="center">{{ $item['code'] ?? '' }}</td>
                        <td class="center">{{ $item['sales_code'] ?? '' }}</td>
                        <td class="center">{{ $item['cost_code'] ?? '' }}</td>
                        <td class="number">{{ $size > 0 ? number_format($size, 2) : '' }}</td>
                        <td class="number">{{ $qty > 0 ? number_format($qty, 0) : '' }}</td>
                        <td class="center">{{ $item['unit'] ?? '' }}</td>
                        <td class="number">{{ $wpu > 0 ? number_format($wpu, 3) : '' }}</td>
                        <td class="number">{{ $rate > 0 ? number_format($rate, 2) : '' }}</td>
                        <td class="number">{{ $totalWeight > 0 ? number_format($totalWeight, 3) : '' }}</td>
                        <td class="number">{{ $totalCost > 0 ? number_format($totalCost, 2) : '' }}</td>
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>

    @include('pdf.partials.footer', ['documentTitle' => 'Detail Sheet', 'estimation' => $estimation])
</body>
</html>
