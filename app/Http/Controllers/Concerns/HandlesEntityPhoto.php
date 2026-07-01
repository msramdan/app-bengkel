<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait HandlesEntityPhoto
{
    protected function photoRules(bool $required = false): array
    {
        $rule = $required ? 'required' : 'nullable';

        return [
            'photo' => [$rule, 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'remove_photo' => ['nullable', 'boolean'],
        ];
    }

    protected function resolvePhotoPath(
        Request $request,
        string $directory,
        ?string $currentPath = null,
    ): ?string {
        if ($request->boolean('remove_photo')) {
            $this->deletePhotoFile($currentPath);

            return null;
        }

        if (! $request->hasFile('photo')) {
            return $currentPath;
        }

        /** @var UploadedFile $file */
        $file = $request->file('photo');
        $this->deletePhotoFile($currentPath);

        $filename = Str::uuid().'.'.$file->getClientOriginalExtension();

        return $file->storeAs($directory, $filename, 'public');
    }

    protected function deletePhotoFile(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
