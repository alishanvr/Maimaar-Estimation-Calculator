<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>RAWMAT - {{ $estimation->quote_number }}</title>
    <style>
        @include('pdf.partials.styles', ['bodyFontSize' => '10px', 'bodyLineHeight' => '1.3'])

        /* === RAWMAT-specific styles === */
        th {
            background-color: {{ $headerColor ?? '#1e3a5f' }};
            color: #ffffff;
            font-weight: bold;
            text-align: center;
            padding: 6px 4px;
            font-size: 9px;
            border: 1px solid {{ $headerColor ?? '#1e3a5f' }};
        }
        td {
            padding: 4px 6px;
            border: 1px solid #ccc;
            font-size: 9px;
        }
        .total-row td {
            font-size: 10px;
        }
        .category-header td {
            background-color: #e8edf3;
            font-weight: bold;
            font-size: 9px;
            padding: 5px 6px;
        }
        .summary-box {
            margin-bottom: 12px;
            border: 1px solid #ccc;
            padding: 8px 12px;
            font-size: 10px;
        }
        .summary-box span {
            margin-right: 24px;
        }
        .summary-box strong {
            color: {{ $headerColor ?? '#1e3a5f' }};
        }
    </style>
</head>
<body>
    @include('pdf.partials.header', ['documentTitle' => 'Raw Material Summary (RAWMAT)', 'estimation' => $estimation])

    {{-- Summary stats --}}
    <div class="summary-box">
        <span><strong>Detail Items:</strong> {{ $rawmatData['summary']['total_items_before'] ?? 0 }}</span>
        <span><strong>Unique Materials:</strong> {{ $rawmatData['summary']['unique_materials'] ?? 0 }}</span>
        <span><strong>Total Weight:</strong> {{ number_format(($rawmatData['summary']['total_weight_kg'] ?? 0) / 1000, 4) }} MT</span>
        <span><strong>Categories:</strong> {{ $rawmatData['summary']['category_count'] ?? 0 }}</span>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 10%;">DB Code</th>
                <th style="width: 10%;">Cost Code</th>
                <th style="width: 25%;">Description</th>
                <th style="width: 5%;">Unit</th>
                <th style="width: 10%;">QTY</th>
                <th style="width: 10%;">Unit Wt (kg)</th>
                <th style="width: 12%;">Total Wt (kg)</th>
                <th style="width: 14%;">Category</th>
            </tr>
        </thead>
        <tbody>
            @php $currentCategory = null; @endphp
            @foreach ($rawmatData['items'] as $item)
                @if ($item['category'] !== $currentCategory)
                    @php $currentCategory = $item['category']; @endphp
                    <tr class="category-header">
                        <td colspan="9">{{ $currentCategory }} ({{ $rawmatData['categories'][$currentCategory]['count'] ?? 0 }} items &mdash; {{ number_format($rawmatData['categories'][$currentCategory]['weight_kg'] ?? 0, 2) }} kg)</td>
                    </tr>
                @endif
                <tr>
                    <td class="center">{{ $item['no'] }}</td>
                    <td>{{ $item['code'] }}</td>
                    <td>{{ $item['cost_code'] }}</td>
                    <td>{{ $item['description'] }}</td>
                    <td class="center">{{ $item['unit'] }}</td>
                    <td class="number">{{ number_format($item['quantity'], 2) }}</td>
                    <td class="number">{{ number_format($item['unit_weight'], 4) }}</td>
                    <td class="number">{{ number_format($item['total_weight'], 2) }}</td>
                    <td>{{ $item['category'] }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="5" style="text-align: right;"><strong>Total</strong></td>
                <td class="number"><strong>{{ $rawmatData['summary']['unique_materials'] ?? 0 }} items</strong></td>
                <td></td>
                <td class="number"><strong>{{ number_format($rawmatData['summary']['total_weight_kg'] ?? 0, 2) }}</strong></td>
                <td></td>
            </tr>
        </tbody>
    </table>

    @include('pdf.partials.footer', ['documentTitle' => 'Raw Material Summary (RAWMAT)', 'estimation' => $estimation])
</body>
</html>
