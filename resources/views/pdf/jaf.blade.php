@php $cur = $currencyCode ?? 'AED'; $xr = $exchangeRate ?? 1.0; @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>JAF - {{ $estimation->quote_number }}</title>
    <style>
        @include('pdf.partials.styles', ['bodyFontSize' => '10px', 'bodyLineHeight' => '1.4'])

        /* === JAF-specific styles === */
        .section {
            margin: 0 16px 14px;
        }
        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: {{ $headerColor ?? '#1e3a5f' }};
            border-bottom: 2px solid {{ $headerColor ?? '#1e3a5f' }};
            padding-bottom: 4px;
            margin-bottom: 8px;
        }
        .info-grid td {
            padding: 4px 8px;
            border: 1px solid #e0e0e0;
            vertical-align: top;
        }
        .info-grid .label {
            background-color: #f5f7fa;
            font-weight: bold;
            color: #333;
            width: 30%;
        }
        .info-grid .value {
            width: 20%;
        }
        .pricing-table td {
            padding: 5px 8px;
            border: 1px solid #e0e0e0;
        }
        .pricing-table .label {
            background-color: #f5f7fa;
            font-weight: bold;
            color: #333;
            width: 55%;
        }
        .pricing-table .value {
            text-align: right;
            width: 45%;
        }
        .pricing-table .highlight-cell {
            background-color: #e8f0fe;
            font-weight: bold;
        }
        .requirements-list td {
            padding: 3px 8px;
            border: 1px solid #e0e0e0;
        }
        .requirements-list .num {
            width: 8%;
            text-align: center;
            background-color: #f5f7fa;
            font-weight: bold;
        }
        .requirements-list .desc {
            width: 82%;
        }
        .requirements-list .check {
            width: 10%;
            text-align: center;
        }
    </style>
</head>
<body>
    @include('pdf.partials.header', ['documentTitle' => 'Job Acceptance Form (JAF)', 'estimation' => $estimation])

    {{-- Project Information --}}
    <div class="section">
        <div class="section-title">Project Information</div>
        <table class="info-grid">
            <tr>
                <td class="label">Quote Number</td>
                <td class="value">{{ $jafData['project_info']['quote_number'] ?? '-' }}</td>
                <td class="label">Salesperson</td>
                <td class="value">{{ $jafData['project_info']['salesperson_code'] ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Building Name</td>
                <td class="value">{{ $jafData['project_info']['building_name'] ?? '-' }}</td>
                <td class="label">Building No</td>
                <td class="value">{{ $jafData['project_info']['building_number'] ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Project Name</td>
                <td class="value">{{ $jafData['project_info']['project_name'] ?? '-' }}</td>
                <td class="label">Revision</td>
                <td class="value">{{ $jafData['project_info']['revision_number'] ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Customer</td>
                <td class="value">{{ $jafData['project_info']['customer_name'] ?? '-' }}</td>
                <td class="label">Date</td>
                <td class="value">{{ $jafData['project_info']['date'] ?? '-' }}</td>
            </tr>
        </table>
    </div>

    {{-- Pricing --}}
    <div class="section">
        <div class="section-title">Pricing</div>
        <table class="pricing-table">
            <tr>
                <td class="label">Requested Bottom Line Mark-Up</td>
                <td class="value">{{ number_format(($jafData['pricing']['bottom_line_markup'] ?? 0), 4) }}</td>
            </tr>
            <tr>
                <td class="label">Value Added at "L" Line ({{ $cur }}/MT)</td>
                <td class="value">{{ number_format(($jafData['pricing']['value_added_l'] ?? 0) * $xr, 2) }}</td>
            </tr>
            <tr>
                <td class="label">Value Added at "R" Line ({{ $cur }}/MT)</td>
                <td class="value">{{ number_format(($jafData['pricing']['value_added_r'] ?? 0) * $xr, 2) }}</td>
            </tr>
            <tr>
                <td class="label highlight-cell">Total Weight (MT)</td>
                <td class="value highlight-cell">{{ number_format(($jafData['pricing']['total_weight_mt'] ?? 0), 4) }}</td>
            </tr>
            <tr>
                <td class="label">Primary Weight (MT)</td>
                <td class="value">{{ number_format(($jafData['pricing']['primary_weight_mt'] ?? 0), 4) }}</td>
            </tr>
            <tr>
                <td class="label highlight-cell">Supply Price ({{ $cur }})</td>
                <td class="value highlight-cell">{{ number_format(($jafData['pricing']['supply_price_aed'] ?? 0) * $xr, 2) }}</td>
            </tr>
            <tr>
                <td class="label">Erection Price ({{ $cur }})</td>
                <td class="value">{{ number_format(($jafData['pricing']['erection_price_aed'] ?? 0) * $xr, 2) }}</td>
            </tr>
            <tr>
                <td class="label highlight-cell">Total Contract ({{ $cur }})</td>
                <td class="value highlight-cell">{{ number_format(($jafData['pricing']['total_contract_aed'] ?? 0) * $xr, 2) }}</td>
            </tr>
            <tr>
                <td class="label">Contract Value (USD)</td>
                <td class="value">{{ number_format(($jafData['pricing']['contract_value_usd'] ?? 0), 0) }}</td>
            </tr>
            <tr>
                <td class="label">Price Per MT ({{ $cur }})</td>
                <td class="value">{{ number_format(($jafData['pricing']['price_per_mt'] ?? 0) * $xr, 2) }}</td>
            </tr>
            <tr>
                <td class="label">Min Delivery (Weeks)</td>
                <td class="value">{{ $jafData['pricing']['min_delivery_weeks'] ?? '-' }}</td>
            </tr>
        </table>
    </div>

    {{-- Building Information --}}
    <div class="section">
        <div class="section-title">Building Information</div>
        <table class="pricing-table">
            <tr>
                <td class="label">Number of Non-Identical Buildings</td>
                <td class="value">{{ $jafData['building_info']['num_non_identical_buildings'] ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Number of All Buildings</td>
                <td class="value">{{ $jafData['building_info']['num_all_buildings'] ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Scope</td>
                <td class="value">{{ $jafData['building_info']['scope'] ?? '-' }}</td>
            </tr>
        </table>
    </div>

    {{-- Special Requirements --}}
    <div class="section">
        <div class="section-title">Special Requirements</div>
        <table class="requirements-list">
            @foreach ($jafData['special_requirements'] ?? [] as $num => $requirement)
                @if (!empty($requirement))
                    <tr>
                        <td class="num">{{ $num }}</td>
                        <td class="desc">{{ $requirement }}</td>
                        <td class="check">&square;</td>
                    </tr>
                @endif
            @endforeach
        </table>
    </div>

    @include('pdf.partials.footer', ['documentTitle' => 'Job Acceptance Form (JAF)', 'estimation' => $estimation])
</body>
</html>
