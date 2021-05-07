<?php

/**
 * List of possible states.
 * @OA\Schema(
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
  public static function getRandomValue() {
    $oClass = new ReflectionClass(__CLASS__);
    $cases = $oClass->getConstants();
    $keys = array_keys($cases);
    return $cases[$keys[rand(0, count($cases)-1)]];
  }
}

/**
 * To visually distinguish in the front end, each participant is assigned its own avatar.
 * @OA\Schema()
 */
class Avatar {

    /**
     * The avatar color.
     * @var string
     * @OA\Property(example="red")
     */
    public $color;

    /**
     * The avatar symbol.
     * @var string
     * @OA\Property(ref="#/components/schemas/AvatarSymbol")
     */
    public $symbol;

    public function __construct(array $data = null)
    {
        $this->color = isset($data['color']) ? $data['color'] : null;
        $this->symbol = isset($data['symbol']) ? $data['symbol'] : null;
    }
}

?>
