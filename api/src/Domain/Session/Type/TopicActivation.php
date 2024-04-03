<?php

namespace App\Domain\Session\Type;

/**
 * Topic activation type for the session.
 * @OA\Schema(
 *   description="Topic activation type for the session",
 *   type="string",
 *   enum={"ALWAYS", "AFTER_PREVIOUS_STARTED", "AFTER_PREVIOUS_FINISHED"},
 *   example="ALWAYS"
 * )
 */
class TopicActivation
{
    /** @var string */
    public const ALWAYS = "always";
    /** @var string */
    public const AFTER_PREVIOUS_STARTED = "after_previous_started";
    /** @var string */
    public const AFTER_PREVIOUS_FINISHED = "after_previous_finished";
}
