<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Recap - {{ $estimation->quote_number }}</title>
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
        .section {
            margin: 0 20px 16px;
        }
        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #1e3a5f;
            border-bottom: 2px solid #1e3a5f;
            padding-bottom: 4px;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
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
        .highlight td {
            background-color: #e8f0fe;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 9px;
            color: #888;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Estimation Recap (Summary)</h1>
        <p>Maimaar Estimation Calculator</p>
    </div>

    <div class="project-info">
        <span><strong>Quote:</strong> {{ $estimation->quote_number }}</span>
        <span><strong>Building:</strong> {{ $estimation->building_name }}</span>
        <span><strong>Customer:</strong> {{ $estimation->customer_name }}</span>
        <span><strong>Date:</strong> {{ $estimation->estimation_date ? \Carbon\Carbon::parse($estimation->estimation_date)->format('d M Y') : '-' }}</span>
    </div>

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
                <td class="label">Total Price (AED)</td>
                <td class="value">{{ number_format($recapData['total_price_aed'] ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td class="label">FOB Price (AED)</td>
                <td class="value">{{ number_format($recapData['fob_price_aed'] ?? 0, 2) }}</td>
            </tr>
            <tr class="highlight">
                <td class="label">Price per MT (AED/MT)</td>
                <td class="value">{{ number_format($recapData['price_per_mt'] ?? 0, 2) }}</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Generated on {{ now()->format('d M Y H:i') }} &bull; Maimaar Estimation Calculator
    </div>
</body>
</html>
