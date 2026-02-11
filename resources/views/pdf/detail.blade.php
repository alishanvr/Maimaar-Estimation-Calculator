<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Detail - {{ $estimation->quote_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 9px;
            color: #1a1a1a;
            line-height: 1.3;
        }
        .header {
            background-color: #1e3a5f;
            color: #ffffff;
            padding: 10px 16px;
            margin-bottom: 12px;
        }
        .header h1 {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .header p {
            font-size: 9px;
            opacity: 0.85;
        }
        .project-info {
            margin: 0 16px 12px;
            padding: 6px 10px;
            background-color: #f5f7fa;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 9px;
        }
        .project-info span {
            margin-right: 20px;
        }
        .project-info strong {
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 0 auto;
        }
        th {
            background-color: #1e3a5f;
            color: #ffffff;
            font-weight: bold;
            text-align: center;
            padding: 5px 4px;
            font-size: 8px;
            border: 1px solid #1e3a5f;
        }
        td {
            padding: 3px 4px;
            border: 1px solid #ccc;
            font-size: 8px;
        }
        td.number {
            text-align: right;
        }
        td.center {
            text-align: center;
        }
        tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .header-row td {
            font-weight: bold;
            background-color: #e2e8f0;
            color: #1e3a5f;
            font-size: 9px;
            border-top: 2px solid #1e3a5f;
        }
        .footer {
            margin-top: 16px;
            text-align: center;
            font-size: 8px;
            color: #888;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Detail Sheet (Bill of Materials)</h1>
        <p>Maimaar Estimation Calculator</p>
    </div>

    <div class="project-info">
        <span><strong>Quote:</strong> {{ $estimation->quote_number }}</span>
        <span><strong>Building:</strong> {{ $estimation->building_name }}</span>
        <span><strong>Customer:</strong> {{ $estimation->customer_name }}</span>
        <span><strong>Date:</strong> {{ $estimation->estimation_date ? \Carbon\Carbon::parse($estimation->estimation_date)->format('d M Y') : '-' }}</span>
    </div>

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

    <div class="footer">
        Generated on {{ now()->format('d M Y H:i') }} &bull; Maimaar Estimation Calculator
    </div>
</body>
</html>
