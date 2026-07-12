@php
    $unitLabel = $item->unit
        ? ($item->unit->abbreviation ? $item->unit->name.' ('.$item->unit->abbreviation.')' : $item->unit->name)
        : '';
@endphp
<option value="{{ $item->id }}"
    data-code="{{ $item->code }}"
    data-name="{{ $item->name }}"
    data-stock="{{ $item->stock }}"
    data-price="{{ $item->selling_price }}"
    data-member-price="{{ $item->member_price ?? 0 }}"
    data-purchase-price="{{ $item->purchase_price ?? 0 }}"
    data-category="{{ $item->category?->name ?? '' }}"
    data-unit="{{ $unitLabel }}"
    data-photo="{{ $item->photo_url ?? '' }}">
    {{ $item->code }} — {{ $item->name }}
    @if ($item->category?->name) · {{ $item->category->name }} @endif
    (Stok: {{ number_format($item->stock) }})
</option>
