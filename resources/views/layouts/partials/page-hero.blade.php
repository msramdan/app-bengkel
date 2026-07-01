@php
    $allItems = collect($items);
    $currentItem = $allItems->last();
    $parentItems = $allItems->slice(0, -1)->filter(
        fn ($item) => ($item['label'] ?? '') !== 'Dashboard'
    );
@endphp

<header class="page-head">
    <div class="page-head-row">
        <div class="page-head-text">
            <h1 class="page-head-title">{{ $title }}</h1>
            @if (! empty($subtitle))
                <p class="page-head-sub">{{ $subtitle }}</p>
            @endif
        </div>
    </div>

    @if ($allItems->count() > 1)
        <nav class="page-trail" aria-label="breadcrumb">
            <a href="{{ route('dashboard') }}" class="page-trail-home" title="Dashboard">
                <i class="bi bi-house-door"></i>
            </a>
            @foreach ($parentItems as $item)
                <span class="page-trail-sep" aria-hidden="true"><i class="bi bi-chevron-right"></i></span>
                @if (! empty($item['url']))
                    <a href="{{ $item['url'] }}" class="page-trail-link">{{ $item['label'] }}</a>
                @else
                    <span class="page-trail-link">{{ $item['label'] }}</span>
                @endif
            @endforeach
            <span class="page-trail-sep" aria-hidden="true"><i class="bi bi-chevron-right"></i></span>
            <span class="page-trail-current">{{ $currentItem['label'] ?? $title }}</span>
        </nav>
    @endif
</header>
