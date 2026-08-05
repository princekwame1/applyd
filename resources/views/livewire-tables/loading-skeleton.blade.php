@props(['colCount' => 5])
@php $cols = max(1, (int) $colCount); @endphp
<div class="tbl-skeleton" aria-hidden="true">
    @for ($r = 0; $r < 6; $r++)
        <div class="tbl-skeleton-row">
            @for ($c = 0; $c < $cols; $c++)
                <span class="skeleton-bar" style="width: {{ [70, 55, 85, 45, 65, 50, 75][($r + $c) % 7] }}%"></span>
            @endfor
        </div>
    @endfor
</div>
