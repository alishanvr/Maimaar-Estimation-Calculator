<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports Dashboard</title>
    <style>
        @include('pdf.partials.styles', ['bodyFontSize' => '10px', 'bodyLineHeight' => '1.4'])

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
            padding: 6px 10px;
            border: 1px solid #e0e0e0;
            font-size: 10px;
        }
        th {
            padding: 6px 10px;
            border: 1px solid #ccc;
            background-color: {{ $headerColor ?? '#1e3a5f' }};
            color: #fff;
            font-size: 10px;
            font-weight: bold;
        }
        .label-cell {
            background-color: #f5f7fa;
            font-weight: bold;
            color: #333;
            width: 50%;
        }
        .value-cell {
            text-align: right;
            width: 50%;
            font-family: 'DejaVu Sans Mono', monospace;
        }
        .kpi-grid {
            width: 100%;
            margin-bottom: 16px;
        }
        .kpi-grid td {
            width: 25%;
            text-align: center;
            vertical-align: top;
            padding: 10px;
            border: 1px solid #e0e0e0;
        }
        .kpi-value {
            font-size: 16px;
            font-weight: bold;
            color: {{ $headerColor ?? '#1e3a5f' }};
            margin-bottom: 2px;
        }
        .kpi-label {
            font-size: 9px;
            color: #666;
            text-transform: uppercase;
        }
        .filter-info {
            margin: 0 20px 14px;
            padding: 7px 12px;
            background-color: #f5f7fa;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 9px;
            color: #555;
        }
    </style>
</head>
<body>
    {{-- Letterhead --}}
    <div class="letterhead">
        <table class="letterhead-row">
            <tr>
                <td class="letterhead-logo" style="border: none;">
                    @if(!empty($logoPath))
                        <img src="{{ $logoPath }}" alt="Logo">
                    @endif
                </td>
                <td class="letterhead-title" style="border: none;">
                    <div class="company-name">{{ $companyName ?? 'Company' }}</div>
                    <div class="doc-title">Reports Dashboard</div>
                </td>
                <td class="letterhead-meta" style="border: none;">
                    <div><span class="meta-label">Generated:</span> <span class="meta-value">{{ $generatedAt }}</span></div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Applied Filters --}}
    @php
        $appliedFilters = [];
        if (!empty($filters['date_from'])) $appliedFilters[] = 'From: ' . $filters['date_from'];
        if (!empty($filters['date_to'])) $appliedFilters[] = 'To: ' . $filters['date_to'];
        if (!empty($filters['statuses'])) $appliedFilters[] = 'Status: ' . implode(', ', $filters['statuses']);
        if (!empty($filters['customer_name'])) $appliedFilters[] = 'Customer: ' . $filters['customer_name'];
        if (!empty($filters['salesperson_code'])) $appliedFilters[] = 'Salesperson: ' . $filters['salesperson_code'];
    @endphp
    @if(count($appliedFilters) > 0)
        <div class="filter-info">
            <strong>Filters:</strong> {{ implode(' | ', $appliedFilters) }}
        </div>
    @endif

    {{-- KPI Summary --}}
    <div class="section">
        <div class="section-title">Key Performance Indicators</div>
        <table class="kpi-grid">
            <tr>
                <td style="border: 1px solid #e0e0e0;">
                    <div class="kpi-value">{{ number_format($data['kpis']['total_estimations']) }}</div>
                    <div class="kpi-label">Total Estimations</div>
                </td>
                <td style="border: 1px solid #e0e0e0;">
                    <div class="kpi-value">{{ number_format($data['kpis']['total_weight_mt'], 2) }}</div>
                    <div class="kpi-label">Total Weight (MT)</div>
                </td>
                <td style="border: 1px solid #e0e0e0;">
                    <div class="kpi-value">{{ number_format($data['kpis']['total_revenue_aed'], 0) }}</div>
                    <div class="kpi-label">Total Revenue (AED)</div>
                </td>
                <td style="border: 1px solid #e0e0e0;">
                    <div class="kpi-value">{{ number_format($data['kpis']['avg_price_per_mt'], 2) }}</div>
                    <div class="kpi-label">Avg Price / MT (AED)</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Status Breakdown --}}
    <div class="section">
        <div class="section-title">Status Breakdown</div>
        <table>
            <tr>
                <td class="label-cell">Draft</td>
                <td class="value-cell">{{ number_format($data['kpis']['draft_count']) }}</td>
            </tr>
            <tr>
                <td class="label-cell">Calculated</td>
                <td class="value-cell">{{ number_format($data['kpis']['calculated_count']) }}</td>
            </tr>
            <tr>
                <td class="label-cell">Finalized</td>
                <td class="value-cell">{{ number_format($data['kpis']['finalized_count']) }}</td>
            </tr>
        </table>
    </div>

    {{-- Weight Distribution --}}
    <div class="section">
        <div class="section-title">Weight Distribution</div>
        <table>
            <tr>
                <td class="label-cell">Steel Weight (kg)</td>
                <td class="value-cell">{{ number_format($data['weight_distribution']['steel_weight_kg'], 1) }}</td>
            </tr>
            <tr>
                <td class="label-cell">Panels Weight (kg)</td>
                <td class="value-cell">{{ number_format($data['weight_distribution']['panels_weight_kg'], 1) }}</td>
            </tr>
            @php $totalDistKg = $data['weight_distribution']['steel_weight_kg'] + $data['weight_distribution']['panels_weight_kg']; @endphp
            @if($totalDistKg > 0)
                <tr class="highlight">
                    <td class="label-cell">Steel %</td>
                    <td class="value-cell">{{ number_format($data['weight_distribution']['steel_weight_kg'] / $totalDistKg * 100, 1) }}%</td>
                </tr>
                <tr class="highlight">
                    <td class="label-cell">Panels %</td>
                    <td class="value-cell">{{ number_format($data['weight_distribution']['panels_weight_kg'] / $totalDistKg * 100, 1) }}%</td>
                </tr>
            @endif
        </table>
    </div>

    {{-- Top Customers --}}
    @if(count($data['customer_revenue']) > 0)
        <div class="section">
            <div class="section-title">Top Customers by Revenue</div>
            <table>
                <thead>
                    <tr>
                        <th style="text-align: left;">Customer</th>
                        <th style="text-align: right;">Revenue (AED)</th>
                        <th style="text-align: right;">Estimations</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['customer_revenue'] as $customer)
                        <tr>
                            <td>{{ $customer['customer_name'] }}</td>
                            <td class="value-cell">{{ number_format($customer['total_price_aed'], 0) }}</td>
                            <td class="value-cell">{{ $customer['estimation_count'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Monthly Trends --}}
    @if(count($data['monthly_trends']) > 0)
        <div class="section">
            <div class="section-title">Monthly Trends</div>
            <table>
                <thead>
                    <tr>
                        <th style="text-align: left;">Month</th>
                        <th style="text-align: right;">Count</th>
                        <th style="text-align: right;">Revenue (AED)</th>
                        <th style="text-align: right;">Weight (MT)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['monthly_trends'] as $trend)
                        <tr>
                            <td>{{ $trend['label'] }}</td>
                            <td class="value-cell">{{ $trend['count'] }}</td>
                            <td class="value-cell">{{ number_format($trend['revenue'], 0) }}</td>
                            <td class="value-cell">{{ number_format($trend['weight_mt'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Footer --}}
    <div style="margin: 20px 20px 0;">
        <div class="footer">
            <span class="footer-left">{{ $footerText ?? '' }}</span>
            <span class="footer-center">Reports Dashboard</span>
            <span class="footer-right">{{ $generatedAt }}</span>
        </div>
    </div>
</body>
</html>
