<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProductImageService
{
    public const DISK = 'public';

    public const DIRECTORY = 'products';

    public const MAX_KILOBYTES = 2048;

    public const MIMES = ['jpeg', 'png', 'webp'];

    /**
     * Validate and store a product image, returning its relative path.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(UploadedFile $image): string
    {
        $this->validateImage($image);

        $path = $image->store(self::DIRECTORY, self::DISK);

        if ($path === false) {
            throw new \RuntimeException(__('Unable to store the product image.'));
        }

        return $path;
    }

    /**
     * Replace an existing image, deleting the previous file first.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function replace(?string $currentPath, UploadedFile $image): string
    {
        $this->validateImage($image);

        $path = $image->store(self::DIRECTORY, self::DISK);

        if ($path === false) {
            throw new \RuntimeException(__('Unable to store the product image.'));
        }

        $this->delete($currentPath);

        return $path;
    }

    /**
     * Remove a stored image if one exists.
     */
    public function delete(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        Storage::disk(self::DISK)->delete($path);
    }

    public function url(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        return Storage::disk(self::DISK)->url($path);
    }

    /**
     * Validate size and format (max 2MB, jpeg/png/webp).
     */
    protected function validateImage(UploadedFile $image): void
    {
        Validator::make(
            ['image' => $image],
            ['image' => ['required', 'image', 'mimes:'.implode(',', self::MIMES), 'max:'.self::MAX_KILOBYTES]]
        )->validate();
    }
}