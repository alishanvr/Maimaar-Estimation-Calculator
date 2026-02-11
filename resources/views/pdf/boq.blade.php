<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BOQ - {{ $estimation->quote_number }}</title>
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
        <h1>Bill of Quantities (BOQ)</h1>
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
                <th style="width: 5%;">SL No</th>
                <th style="width: 45%;">Item Description</th>
                <th style="width: 8%;">Unit</th>
                <th style="width: 12%;">QTY</th>
                <th style="width: 15%;">Unit Rate (AED)</th>
                <th style="width: 15%;">Total Price (AED)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($boqData['items'] as $item)
                <tr>
                    <td class="center">{{ $item['sl_no'] }}</td>
                    <td>{{ $item['description'] }}</td>
                    <td class="center">{{ $item['unit'] }}</td>
                    <td class="number">{{ number_format($item['quantity'], 4) }}</td>
                    <td class="number">{{ number_format($item['unit_rate'], 2) }}</td>
                    <td class="number">{{ number_format($item['total_price'], 2) }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="2" style="text-align: right;">Total</td>
                <td class="center">MT</td>
                <td class="number">{{ number_format($boqData['total_weight_mt'], 4) }}</td>
                <td></td>
                <td class="number">{{ number_format($boqData['total_price'], 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        Generated on {{ now()->format('d M Y H:i') }} &bull; Maimaar Estimation Calculator
    </div>
</body>
</html>
