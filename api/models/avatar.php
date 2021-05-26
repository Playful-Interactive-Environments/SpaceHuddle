<?php

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

/**
 * For visual differentiation in the frontend, each participant is assigned its own avatar.
 * @OA\Schema(description="For visual differentiation in the frontend, each participant is assigned its own avatar.",)
 */
class Avatar {

    /**
     * The avatar color.
     * @var string|null
     * @OA\Property(example="red")
     */
    public ?string $color;

    /**
     * The avatar symbol.
     * @var string|null
     * @OA\Property(ref="#/components/schemas/AvatarSymbol")
     */
    public ?string $symbol;

    public function __construct(array $data = null)
    {
        $this->color = isset($data['color']) ? $data['color'] : null;
        $this->symbol = isset($data['symbol']) ? $data['symbol'] : null;
    }
}

?>
