<div class="col-12">
    <label class="form-label">Foto {{ $label ?? '' }}</label>
    <div class="entity-photo-field">
        <div class="entity-photo-preview" id="entity-photo-preview">
            <img src="" alt="" class="entity-photo-img d-none" id="entity-photo-img">
            <div class="entity-photo-placeholder" id="entity-photo-placeholder">
                <i class="bi {{ $placeholderIcon ?? 'bi-image' }}"></i>
                <span>Belum ada foto</span>
            </div>
        </div>
        <div class="entity-photo-actions">
            <input type="file" name="photo" id="entity-photo-input"
                class="form-control form-control-clean" accept="image/jpeg,image/jpg,image/png,image/webp">
            <div class="form-hint-sm">JPG, PNG, atau WEBP. Maks. 2 MB.</div>
            <div class="form-check mt-2 d-none" id="entity-remove-photo-wrap">
                <input type="checkbox" name="remove_photo" value="1" class="form-check-input" id="entity-remove-photo">
                <label class="form-check-label" for="entity-remove-photo">Hapus foto saat ini</label>
            </div>
        </div>
    </div>
</div>
