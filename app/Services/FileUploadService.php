<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class FileUploadService
{
    public function uploadLetterFile(UploadedFile $file, string $directory, string $identifier): string
    {
        $safeIdentifier = Str::of($identifier)->replace(['/', '\\'], '-')->slug('-');
        $filename = $safeIdentifier.'-'.now()->format('YmdHis').'.'.$file->getClientOriginalExtension();

        return $file->storeAs($directory, $filename, 'local');
    }
}
