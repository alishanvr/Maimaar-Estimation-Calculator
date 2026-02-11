<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SAL - {{ $estimation->quote_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            color: #1a1a1a;
            line-height: 1.4;
        }
        .header {
            background-color: #1e3a5f;
            color: #ffffff;
            padding: 12px 20px;
            margin-bottom: 16px;
        }
        .header h1 {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .header p {
            font-size: 10px;
            opacity: 0.85;
        }
        .project-info {
            margin: 0 20px 16px;
            padding: 8px 12px;
            background-color: #f5f7fa;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 10px;
        }
        .project-info span {
            margin-right: 24px;
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
            padding: 8px 6px;
            font-size: 10px;
            border: 1px solid #1e3a5f;
        }
        td {
            padding: 6px 8px;
            border: 1px solid #ccc;
            font-size: 10px;
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
        .total-row td {
            font-weight: bold;
            background-color: #e8f0fe;
            border-top: 2px solid #1e3a5f;
            font-size: 11px;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 9px;
            color: #888;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>SAL (Sales Analysis)</h1>
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
                <th style="width: 6%;">Code</th>
                <th style="width: 32%;">Description</th>
                <th style="width: 12%;">Weight (kg)</th>
                <th style="width: 14%;">Cost (AED)</th>
                <th style="width: 8%;">Markup</th>
                <th style="width: 14%;">Price (AED)</th>
                <th style="width: 14%;">AED/MT</th>
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
                    <td class="number">{{ number_format($line['cost'] ?? 0, 2) }}</td>
                    <td class="number">{{ number_format($line['markup'] ?? 0, 3) }}</td>
                    <td class="number">{{ number_format($line['price'] ?? 0, 2) }}</td>
                    <td class="number">{{ number_format($line['price_per_mt'] ?? 0, 2) }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td class="center"></td>
                <td>TOTAL</td>
                <td class="number">{{ number_format($salData['total_weight_kg'] ?? 0, 3) }}</td>
                <td class="number">{{ number_format($salData['total_cost'] ?? 0, 2) }}</td>
                <td class="number">{{ number_format($salData['markup_ratio'] ?? 0, 3) }}</td>
                <td class="number">{{ number_format($salData['total_price'] ?? 0, 2) }}</td>
                <td class="number">{{ number_format($salData['price_per_mt'] ?? 0, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        Generated on {{ now()->format('d M Y H:i') }} &bull; Maimaar Estimation Calculator
    </div>
</body>
</html>
