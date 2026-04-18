@php
    $width = $width ?? 100;
    $additionalCssClass = $additionalCssClass ?? '';
    $total = $yes + $no;
@endphp
@if ($total > 0)
<div class="feedback-graph {{ $additionalCssClass }}">
    @if ($yes)<span class="yes" style="width: {{ min(96, round(($yes / $total) * $width)) }}px"><span>{{ $yes }}</span></span>@endif
    @if ($no) <span class="no"  style="width: {{ min(96, round(($no / $total) * $width)) }}px"><span>{{ $no }}</span></span>@endif
</div>
@endif
