{{-- Shared PDF letterhead header --}}
{{-- Required vars: $documentTitle, $estimation --}}
{{-- Optional vars: $logoPath, $companyName --}}

<div class="letterhead">
    <table class="letterhead-row" cellpadding="0" cellspacing="0">
        <tr>
            <td class="letterhead-logo">
                @if (!empty($logoPath))
                    <img src="{{ $logoPath }}" alt="Logo">
                @elseif (file_exists(public_path('images/logo.jpeg')))
                    <img src="{{ public_path('images/logo.jpeg') }}" alt="Logo">
                @elseif (file_exists(public_path('images/logo.png')))
                    <img src="{{ public_path('images/logo.png') }}" alt="Logo">
                @endif
            </td>
            <td class="letterhead-title">
                <div class="company-name">{{ $companyName ?? 'Maimaar Group' }}</div>
                <div class="doc-title">{{ $documentTitle }}</div>
            </td>
            <td class="letterhead-meta">
                <div>
                    <span class="meta-label">Quote:</span>
                    <span class="meta-value">{{ $estimation->quote_number }}</span>
                </div>
                <div>
                    <span class="meta-label">Date:</span>
                    <span class="meta-value">{{ $estimation->estimation_date ? \Carbon\Carbon::parse($estimation->estimation_date)->format('d M Y') : now()->format('d M Y') }}</span>
                </div>
            </td>
        </tr>
    </table>
</div>

<div class="project-info">
    <span><strong>Building:</strong> {{ $estimation->building_name }}</span>
    <span><strong>Customer:</strong> {{ $estimation->customer_name }}</span>
    @if ($estimation->project_name)
        <span><strong>Project:</strong> {{ $estimation->project_name }}</span>
    @endif
    @if ($estimation->revision_no)
        <span><strong>Rev:</strong> {{ $estimation->revision_no }}</span>
    @endif
</div>
