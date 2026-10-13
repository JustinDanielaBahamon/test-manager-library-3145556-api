<?php

namespace App\Adapters\Storage;

use App\Application\Ports\CoverStorage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** D-08: covers on local disk; only an opaque key goes to the database. */
final class LocalCoverStorage implements CoverStorage
{
    public function __construct(private readonly string $directory)
    {
    }

    public function put(string $contents, string $extension): string
    {
        $key = Str::uuid().'.'.$extension;
        Storage::disk('local')->put($this->directory.'/'.$key, $contents);

        return $key;
    }

    public function get(string $key): ?string
    {
        $path = $this->directory.'/'.basename($key);

        return Storage::disk('local')->exists($path) ? Storage::disk('local')->get($path) : null;
    }

    public function delete(string $key): void
    {
        Storage::disk('local')->delete($this->directory.'/'.basename($key));
    }
}
