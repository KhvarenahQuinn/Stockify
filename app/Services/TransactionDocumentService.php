<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * Storage for the delivery note or packing slip attached to a movement.
 *
 * Documents are private: they can contain supplier pricing, signatures and
 * delivery addresses, so they live on the local disk and are streamed through
 * an authenticated controller action. Product images and the app logo remain
 * on the public disk because they are intentionally public.
 */
class TransactionDocumentService
{
    public const DISK = 'local';

    public const DIRECTORY = 'transactions';

    public const MAX_KILOBYTES = 5120;

    /**
     * Delivery notes are scanned paper, so PDF is the common case; images are
     * accepted because a phone photo of a signed note is the usual fallback.
     *
     * @var array<int, string>
     */
    public const MIMES = ['pdf', 'jpeg', 'jpg', 'png', 'webp'];

    /**
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(UploadedFile $document): string
    {
        $this->validateDocument($document);

        $path = $document->store(self::DIRECTORY, self::DISK);

        if ($path === false) {
            throw new \RuntimeException(__('Unable to store the supporting document.'));
        }

        return $path;
    }

    public function delete(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        Storage::disk(self::DISK)->delete($path);
    }

    /**
     * Human label for the stored file, falling back to the raw path so a link
     * never renders as an empty anchor.
     */
    public function label(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        return basename($path);
    }

    protected function validateDocument(UploadedFile $document): void
    {
        Validator::make(
            ['document' => $document],
            [
                'document' => [
                    'required',
                    'file',
                    'mimes:'.implode(',', self::MIMES),
                    'max:'.self::MAX_KILOBYTES,
                ],
            ],
            [
                'document.mimes' => 'The supporting document must be a PDF, JPEG, PNG or WebP file.',
                'document.max' => 'The supporting document may not be larger than 5 MB.',
            ]
        )->validate();
    }
}