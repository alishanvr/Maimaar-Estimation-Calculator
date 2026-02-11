{{-- Shared PDF base styles - included by all PDF templates --}}
{{-- Optional variables: $fontFamilyCss, $headerColor, $bodyFontSize, $bodyLineHeight --}}
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}
body {
    font-family: {!! $fontFamilyCss ?? "'DejaVu Sans', Arial, sans-serif" !!};
    font-size: {{ $bodyFontSize ?? '11px' }};
    color: #1a1a1a;
    line-height: {{ $bodyLineHeight ?? '1.4' }};
}

/* ===== Letterhead ===== */
.letterhead {
    padding: 12px 20px 10px;
    border-bottom: 2px solid {{ $headerColor ?? '#1e3a5f' }};
    margin-bottom: 14px;
}
.letterhead-row {
    width: 100%;
}
.letterhead-logo {
    vertical-align: middle;
    width: 60px;
}
.letterhead-logo img {
    height: 44px;
    width: auto;
}
.letterhead-title {
    vertical-align: middle;
    padding-left: 12px;
}
.letterhead-title .company-name {
    font-size: 14px;
    font-weight: bold;
    color: {{ $headerColor ?? '#1e3a5f' }};
    margin-bottom: 1px;
}
.letterhead-title .doc-title {
    font-size: 11px;
    color: #555;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.letterhead-meta {
    vertical-align: middle;
    text-align: right;
    font-size: 10px;
    color: #444;
}
.letterhead-meta .meta-label {
    color: #888;
    font-size: 9px;
}
.letterhead-meta .meta-value {
    font-weight: bold;
    color: {{ $headerColor ?? '#1e3a5f' }};
}

/* ===== Project Info Bar ===== */
.project-info {
    margin: 0 20px 14px;
    padding: 7px 12px;
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

/* ===== Footer ===== */
.footer {
    margin-top: 20px;
    padding-top: 8px;
    border-top: 1px solid #ccc;
    font-size: 9px;
    color: #888;
}
.footer-left {
    float: left;
}
.footer-center {
    text-align: center;
}
.footer-right {
    float: right;
}

/* ===== Common Table Styles ===== */
table {
    width: 100%;
    border-collapse: collapse;
    margin: 0 auto;
}
td.number {
    text-align: right;
}
td.center {
    text-align: center;
}
td.left {
    text-align: left;
}
.total-row td {
    font-weight: bold;
    background-color: #e8f0fe;
    border-top: 2px solid {{ $headerColor ?? '#1e3a5f' }};
}
.subtotal-row td {
    font-weight: bold;
    background-color: #dbeafe;
    border-top: 1px solid #93c5fd;
}
tr:nth-child(even) {
    background-color: #f9fafb;
}
.highlight td {
    background-color: #e8f0fe;
    font-weight: bold;
}
