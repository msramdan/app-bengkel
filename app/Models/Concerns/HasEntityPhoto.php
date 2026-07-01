<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;

trait HasEntityPhoto
{
    public function photoUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            if (! $this->photo) {
                return null;
            }

            return Storage::disk('public')->url($this->photo);
        });
    }

    public function deletePhotoFile(): void
    {
        if ($this->photo && Storage::disk('public')->exists($this->photo)) {
            Storage::disk('public')->delete($this->photo);
        }
    }
}
