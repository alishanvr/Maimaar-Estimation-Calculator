<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>FCPBS - {{ $estimation->quote_number }}</title>
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
            padding: 5px 3px;
            font-size: 7px;
            border: 1px solid #1e3a5f;
        }
        td {
            padding: 3px 4px;
            border: 1px solid #ccc;
            font-size: 8px;
            text-align: right;
        }
        td.left {
            text-align: left;
        }
        td.center {
            text-align: center;
        }
        tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .subtotal-row td {
            font-weight: bold;
            background-color: #dbeafe;
            border-top: 1px solid #93c5fd;
        }
        .total-row td {
            font-weight: bold;
            background-color: #e8f0fe;
            border-top: 2px solid #1e3a5f;
            font-size: 9px;
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
        <h1>FCPBS (Financial Cost &amp; Price Breakdown)</h1>
        <p>Maimaar Estimation Calculator</p>
    </div>

    <div class="project-info">
        <span><strong>Quote:</strong> {{ $estimation->quote_number }}</span>
        <span><strong>Building:</strong> {{ $estimation->building_name }}</span>
        <span><strong>Customer:</strong> {{ $estimation->customer_name }}</span>
        <span><strong>Date:</strong> {{ $estimation->estimation_date ? \Carbon\Carbon::parse($estimation->estimation_date)->format('d M Y') : '-' }}</span>
    </div>

    @php
        $categoryOrder = ['A', 'B', 'C', 'D'];
        $panelCategories = ['F', 'G', 'H', 'I', 'J'];
        $otherCategories = ['M', 'O', 'Q'];
        $categories = $fcpbsData['categories'] ?? [];
        $steelSub = $fcpbsData['steel_subtotal'] ?? [];
        $panelsSub = $fcpbsData['panels_subtotal'] ?? [];
    @endphp

    <table>
        <thead>
            <tr>
                <th style="width: 3%;">SN</th>
                <th style="width: 14%;">Category</th>
                <th style="width: 5%;">Qty</th>
                <th style="width: 7%;">Weight (kg)</th>
                <th style="width: 5%;">Wt%</th>
                <th style="width: 8%;">Material</th>
                <th style="width: 8%;">Manufg</th>
                <th style="width: 8%;">Overhead</th>
                <th style="width: 8%;">Total Cost</th>
                <th style="width: 5%;">Markup</th>
                <th style="width: 9%;">Selling Price</th>
                <th style="width: 5%;">Sell%</th>
                <th style="width: 6%;">AED/MT</th>
                <th style="width: 6%;">VA</th>
                <th style="width: 5%;">VA/MT</th>
            </tr>
        </thead>
        <tbody>
            {{-- Steel categories (A-D) --}}
            @foreach ($categoryOrder as $key)
                @if (isset($categories[$key]))
                    @php $cat = $categories[$key]; @endphp
                    <tr>
                        <td class="center">{{ $cat['key'] }}</td>
                        <td class="left">{{ $cat['name'] }}</td>
                        <td>{{ number_format($cat['quantity'] ?? 0, 1) }}</td>
                        <td>{{ number_format($cat['weight_kg'] ?? 0, 1) }}</td>
                        <td>{{ number_format($cat['weight_pct'] ?? 0, 1) }}%</td>
                        <td>{{ number_format($cat['material_cost'] ?? 0, 0) }}</td>
                        <td>{{ number_format($cat['manufacturing_cost'] ?? 0, 0) }}</td>
                        <td>{{ number_format($cat['overhead_cost'] ?? 0, 0) }}</td>
                        <td>{{ number_format($cat['total_cost'] ?? 0, 0) }}</td>
                        <td>{{ number_format($cat['markup'] ?? 0, 3) }}</td>
                        <td>{{ number_format($cat['selling_price'] ?? 0, 0) }}</td>
                        <td>{{ number_format($cat['selling_price_pct'] ?? 0, 1) }}%</td>
                        <td>{{ number_format($cat['price_per_mt'] ?? 0, 0) }}</td>
                        <td>{{ number_format($cat['value_added'] ?? 0, 0) }}</td>
                        <td>{{ number_format($cat['va_per_mt'] ?? 0, 0) }}</td>
                    </tr>
                @endif
            @endforeach

            {{-- Steel subtotal --}}
            @if (!empty($steelSub))
                <tr class="subtotal-row">
                    <td class="center"></td>
                    <td class="left">Sub Total (Steel)</td>
                    <td></td>
                    <td>{{ number_format($steelSub['weight_kg'] ?? 0, 1) }}</td>
                    <td></td>
                    <td>{{ number_format($steelSub['material_cost'] ?? 0, 0) }}</td>
                    <td>{{ number_format($steelSub['manufacturing_cost'] ?? 0, 0) }}</td>
                    <td>{{ number_format($steelSub['overhead_cost'] ?? 0, 0) }}</td>
                    <td>{{ number_format($steelSub['total_cost'] ?? 0, 0) }}</td>
                    <td></td>
                    <td>{{ number_format($steelSub['selling_price'] ?? 0, 0) }}</td>
                    <td></td>
                    <td></td>
                    <td>{{ number_format($steelSub['value_added'] ?? 0, 0) }}</td>
                    <td></td>
                </tr>
            @endif

            {{-- Panel categories (F-J) --}}
            @foreach ($panelCategories as $key)
                @if (isset($categories[$key]))
                    @php $cat = $categories[$key]; @endphp
                    <tr>
                        <td class="center">{{ $cat['key'] }}</td>
                        <td class="left">{{ $cat['name'] }}</td>
                        <td>{{ number_format($cat['quantity'] ?? 0, 1) }}</td>
                        <td>{{ number_format($cat['weight_kg'] ?? 0, 1) }}</td>
                        <td>{{ number_format($cat['weight_pct'] ?? 0, 1) }}%</td>
                        <td>{{ number_format($cat['material_cost'] ?? 0, 0) }}</td>
                        <td>{{ number_format($cat['manufacturing_cost'] ?? 0, 0) }}</td>
                        <td>{{ number_format($cat['overhead_cost'] ?? 0, 0) }}</td>
                        <td>{{ number_format($cat['total_cost'] ?? 0, 0) }}</td>
                        <td>{{ number_format($cat['markup'] ?? 0, 3) }}</td>
                        <td>{{ number_format($cat['selling_price'] ?? 0, 0) }}</td>
                        <td>{{ number_format($cat['selling_price_pct'] ?? 0, 1) }}%</td>
                        <td>{{ number_format($cat['price_per_mt'] ?? 0, 0) }}</td>
                        <td>{{ number_format($cat['value_added'] ?? 0, 0) }}</td>
                        <td>{{ number_format($cat['va_per_mt'] ?? 0, 0) }}</td>
                    </tr>
                @endif
            @endforeach

            {{-- Panels subtotal --}}
            @if (!empty($panelsSub))
                <tr class="subtotal-row">
                    <td class="center"></td>
                    <td class="left">Sub Total (Panels)</td>
                    <td></td>
                    <td>{{ number_format($panelsSub['weight_kg'] ?? 0, 1) }}</td>
                    <td></td>
                    <td>{{ number_format($panelsSub['material_cost'] ?? 0, 0) }}</td>
                    <td>{{ number_format($panelsSub['manufacturing_cost'] ?? 0, 0) }}</td>
                    <td>{{ number_format($panelsSub['overhead_cost'] ?? 0, 0) }}</td>
                    <td>{{ number_format($panelsSub['total_cost'] ?? 0, 0) }}</td>
                    <td></td>
                    <td>{{ number_format($panelsSub['selling_price'] ?? 0, 0) }}</td>
                    <td></td>
                    <td></td>
                    <td>{{ number_format($panelsSub['value_added'] ?? 0, 0) }}</td>
                    <td></td>
                </tr>
            @endif

            {{-- FOB Price --}}
            <tr class="total-row">
                <td class="center"></td>
                <td class="left">FOB Price</td>
                <td colspan="8"></td>
                <td>{{ number_format($fcpbsData['fob_price'] ?? 0, 0) }}</td>
                <td colspan="4"></td>
            </tr>

            {{-- Other categories (M, O, Q) --}}
            @foreach ($otherCategories as $key)
                @if (isset($categories[$key]))
                    @php $cat = $categories[$key]; @endphp
                    <tr>
                        <td class="center">{{ $cat['key'] }}</td>
                        <td class="left">{{ $cat['name'] }}</td>
                        <td>{{ number_format($cat['quantity'] ?? 0, 1) }}</td>
                        <td>{{ number_format($cat['weight_kg'] ?? 0, 1) }}</td>
                        <td>{{ number_format($cat['weight_pct'] ?? 0, 1) }}%</td>
                        <td>{{ number_format($cat['material_cost'] ?? 0, 0) }}</td>
                        <td>{{ number_format($cat['manufacturing_cost'] ?? 0, 0) }}</td>
                        <td>{{ number_format($cat['overhead_cost'] ?? 0, 0) }}</td>
                        <td>{{ number_format($cat['total_cost'] ?? 0, 0) }}</td>
                        <td>{{ number_format($cat['markup'] ?? 0, 3) }}</td>
                        <td>{{ number_format($cat['selling_price'] ?? 0, 0) }}</td>
                        <td>{{ number_format($cat['selling_price_pct'] ?? 0, 1) }}%</td>
                        <td>{{ number_format($cat['price_per_mt'] ?? 0, 0) }}</td>
                        <td>{{ number_format($cat['value_added'] ?? 0, 0) }}</td>
                        <td>{{ number_format($cat['va_per_mt'] ?? 0, 0) }}</td>
                    </tr>
                @endif
            @endforeach

            {{-- Total Supply --}}
            <tr class="total-row">
                <td class="center"></td>
                <td class="left">Total Supply</td>
                <td></td>
                <td>{{ number_format($fcpbsData['total_weight_kg'] ?? 0, 1) }}</td>
                <td colspan="6"></td>
                <td>{{ number_format($fcpbsData['total_price'] ?? 0, 0) }}</td>
                <td colspan="4"></td>
            </tr>

            {{-- Category T (Erection) if present --}}
            @if (isset($categories['T']))
                @php $cat = $categories['T']; @endphp
                <tr>
                    <td class="center">{{ $cat['key'] }}</td>
                    <td class="left">{{ $cat['name'] }}</td>
                    <td>{{ number_format($cat['quantity'] ?? 0, 1) }}</td>
                    <td>{{ number_format($cat['weight_kg'] ?? 0, 1) }}</td>
                    <td>{{ number_format($cat['weight_pct'] ?? 0, 1) }}%</td>
                    <td>{{ number_format($cat['material_cost'] ?? 0, 0) }}</td>
                    <td>{{ number_format($cat['manufacturing_cost'] ?? 0, 0) }}</td>
                    <td>{{ number_format($cat['overhead_cost'] ?? 0, 0) }}</td>
                    <td>{{ number_format($cat['total_cost'] ?? 0, 0) }}</td>
                    <td>{{ number_format($cat['markup'] ?? 0, 3) }}</td>
                    <td>{{ number_format($cat['selling_price'] ?? 0, 0) }}</td>
                    <td>{{ number_format($cat['selling_price_pct'] ?? 0, 1) }}%</td>
                    <td>{{ number_format($cat['price_per_mt'] ?? 0, 0) }}</td>
                    <td>{{ number_format($cat['value_added'] ?? 0, 0) }}</td>
                    <td>{{ number_format($cat['va_per_mt'] ?? 0, 0) }}</td>
                </tr>
            @endif

            {{-- Total Contract --}}
            @php
                $totalWeightKg = $fcpbsData['total_weight_kg'] ?? 0;
                $totalPrice = $fcpbsData['total_price'] ?? 0;
                $pricePerMt = $totalWeightKg > 0 ? ($totalPrice / $totalWeightKg) * 1000 : 0;
            @endphp
            <tr class="total-row">
                <td class="center"></td>
                <td class="left">Total Contract</td>
                <td></td>
                <td>{{ number_format($totalWeightKg, 1) }}</td>
                <td colspan="6"></td>
                <td>{{ number_format($totalPrice, 0) }}</td>
                <td></td>
                <td>{{ number_format($pricePerMt, 0) }}</td>
                <td colspan="2"></td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        Generated on {{ now()->format('d M Y H:i') }} &bull; Maimaar Estimation Calculator
    </div>
</body>
</html>
