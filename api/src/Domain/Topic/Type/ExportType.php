<?php

namespace App\Domain\Topic\Type;

/**
 * Possible export types.
 * @OA\Schema(
 *   description="possible export types",
 *   type="string",
 *   enum={"ODS", "XLSX", "XLS"},
 *   example="XLSX"
 * )
 */
class ExportType
{
    public const ODS = "ods";
    public const XLSX = "xlsx";
    public const XLS = "xls";
}
