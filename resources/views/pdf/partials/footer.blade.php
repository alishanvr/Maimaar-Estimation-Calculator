{{-- Shared PDF footer --}}
{{-- Required vars: $documentTitle, $estimation --}}
{{-- Optional vars: $footerText, $showPageNumbers --}}

<div class="footer">
    <span class="footer-left">{{ $estimation->quote_number }} &mdash; {{ $documentTitle }}</span>
    @if (!empty($footerText))
        <span class="footer-center">{{ $footerText }}</span>
    @endif
    <span class="footer-right">Generated {{ now()->format('d M Y H:i') }}</span>
</div>

@if ($showPageNumbers ?? false)
<script type="text/php">
    if (isset($pdf)) {
        $font = $fontMetrics->get_font("DejaVu Sans", "normal");
        $size = 8;
        $color = [0.53, 0.53, 0.53];
        $text = "Page {PAGE_NUM} of {PAGE_COUNT}";
        $width = $fontMetrics->get_text_width($text, $font, $size);
        $pageWidth = $pdf->get_width();
        $pageHeight = $pdf->get_height();
        $x = $pageWidth - $width - 36;
        $y = $pageHeight - 28;
        $pdf->page_text($x, $y, $text, $font, $size, $color);
    }
</script>
@endif
