<?php

namespace App\Domain\Topic\Data;

/**
 * Represents a export.
 * @OA\Schema(description="export description")
 */
class ExportData
{
    /**
     * Filetype.
     * @var string|null
     * @OA\Property()
     */
    public ?string $filetype;

    /**
     * Base64 converted file content.
     * @var string|null
     * @OA\Property()
     */
    public ?string $base64;

    /**
     * Creates a new export.
     * @param string|null $downloadLink Url of the download link.
     * @param string|null $path Url of the download path.
     */
    public function __construct(string|null $downloadLink, string|null $path)
    {
        if ($downloadLink) {
            /*$exportPath = $_SERVER["REQUEST_SCHEME"] . '://' . $_SERVER["HTTP_HOST"] .
                str_replace("index.php", "", $_SERVER["PHP_SELF"]);
            $url = str_replace(DIRECTORY_SEPARATOR, "/", $downloadLink);
            $url = "$exportPath$url";

            $this->downloadLink = $url;*/
            $this->base64 = $this->convertToBase64($downloadLink);
            $extensionPos = strpos($downloadLink, '.', -10) + 1;
            $this->filetype = substr($downloadLink, $extensionPos);


            $files = glob($path . DIRECTORY_SEPARATOR . "*", GLOB_MARK);
            foreach ($files as $file) {
                if (!is_dir($file)) {
                    //unlink($file);
                }
            }
            rmdir($path);
        }
    }

    /**
     * convert file to base64 string
     * @param string $filepath Filename + Path
     * @return string Base64 converted file content
     */
    private function convertToBase64(string $filepath): string
    {
        if (is_file($filepath)) {
            $content = file_get_contents($filepath);
            return base64_encode($content);
        }
        return "";
    }
}
