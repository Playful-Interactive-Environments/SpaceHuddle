<?php

namespace PieLab\GAB\Models;

/**
 * List of possible states.
 * @OA\Schema(
 *   description="Possible avatar symbols.",
 *   type="string",
 *   enum={"STAR", "CIRCLE", "TRIANGLE"},
 *   example="STAR"
 * )
 */
class AvatarSymbol {
  const STAR = "star";
  const CIRCLE = "circle";
  const TRIANGLE = "triangle";


  /**
   * Pick a random value of the AvatarSymbol enum.
   * @return a random AvatarSymbol.
   */
  public static function getRandomValue() : string {
    $oClass = new ReflectionClass(__CLASS__);
    $cases = $oClass->getConstants();
    $keys = array_keys($cases);
    return $cases[$keys[rand(0, count($cases)-1)]];
  }
}

?>
