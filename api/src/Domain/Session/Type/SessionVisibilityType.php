<?php

namespace App\Domain\Session\Type;

/**
 * Visiblity of the session.
 * @OA\Schema(
 *   description="Visibility of the session",
 *   type="string",
 *   enum={"PRIVATE", "PUBLIC", "TEMPLATE"},
 *   example="PRIVATE"
 * )
 */
class SessionVisibilityType
{
    /** @var string */
    public const PRIVATE = "private";

    /** @var string */
    public const PUBLIC = "public";

    /** @var string */
    public const TEMPLATE = "template";
}
