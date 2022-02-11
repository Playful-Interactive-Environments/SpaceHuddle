<?php

namespace App\Domain\Topic\Data;

/**
 * Represents a export.
 * @OA\Schema(description="export description")
 */
class ExportData
{
    /**
     * Download link.
     * @var string|null
     * @OA\Property()
     */
    public ?string $downloadLink;

    /**
     * Creates a new export.
     * @param string|null $downloadLink Url of the download link.
     */
    public function __construct(string|null $downloadLink)
    {
        $this->downloadLink = $downloadLink;
    }
}
