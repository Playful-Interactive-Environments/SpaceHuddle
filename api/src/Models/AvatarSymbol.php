<?php

namespace PieLab\GAB\Models;

use ReflectionClass;

/**
 * List of possible avatar symbols.
 * @OA\Schema(
 *   description="Possible avatar symbols.",
 *   type="string",
 *   enum={"STAR", "CIRCLE", "TRIANGLE"},
 *   example="STAR"
 * )
 */
class AvatarSymbol
{
    public const STAR = "star";
    public const CIRCLE = "circle";
    public const TRIANGLE = "triangle";

    /**
     * Pick a random value of the AvatarSymbol enum.
     * @return string A random AvatarSymbol.
     */
    public static function getRandomValue(): string
    {
        $oClass = new ReflectionClass(__CLASS__);
        $cases = $oClass->getConstants();
        $keys = array_keys($cases);
        return $cases[$keys[rand(0, count($cases) - 1)]];
    }
}
