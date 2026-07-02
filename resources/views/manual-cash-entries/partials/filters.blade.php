<div class="list-toolbar mb-3">
    <div class="d-flex flex-wrap align-items-end gap-2">
        <div>
            <label class="form-label small mb-1" for="filter-from">Dari</label>
            <input type="date" id="filter-from" class="form-control form-control-clean" value="{{ now()->startOfMonth()->toDateString() }}">
        </div>
        <div>
            <label class="form-label small mb-1" for="filter-to">Sampai</label>
            <input type="date" id="filter-to" class="form-control form-control-clean" value="{{ now()->toDateString() }}">
        </div>
        <div class="flex-grow-1" style="min-width: 220px;">
            <label class="form-label small mb-1" for="filter-category">Kategori</label>
            <select id="filter-category" class="form-select form-control-clean">
                <option value="">Semua kategori</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="button" class="btn btn-outline-primary" id="btn-apply-filter">
            <i class="bi bi-funnel"></i> Terapkan
        </button>
    </div>
</div>
