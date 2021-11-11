<?php

namespace App\Domain\Participant\Type;

use ReflectionClass;

/**
 * List of possible avatar symbols.
 * @OA\Schema(
 *   description="Possible avatar symbols.",
 *   type="string",
 *   enum={"STAR", "CIRCLE", "CERTIFICATE"},
 *   example="STAR"
 * )
 */
class AvatarSymbol
{
    public const ANCHOR = "anchor";
    public const ANGRY = "angry";
    public const ARCHWAY = "archway";
    public const ASTERISK = "asterisk";
    public const ATOM = "atom";
    public const BABY = "baby";
    public const BAHAI = "bahai";
    public const BATH = "bath";
    public const BEER = "beer";
    public const BELL = "bell";
    public const BICYCLE = "bicycle";
    public const BINOCULARS = "binoculars";
    public const BLENDER = "blender";
    public const BOMB = "bomb";
    public const BONG = "bong";
    public const BOOK = "book";
    public const BRAIN = "brain";
    public const BROOM = "broom";
    public const BRUSH = "brush";
    public const BUG = "bug";
    public const BUS = "bus";
    public const CAMPGROUND = "campground";
    public const CAR = "car";
    public const CARROT = "carrot";
    public const CAT = "cat";
    public const CENTOS = "centos";
    public const CERTIFICATE = "certificate";
    public const CHAIR = "chair";
    public const CHEESE = "cheese";
    public const CHESS = "chess";
    public const CHILD = "child";
    public const CHURCH = "church";
    public const CIRCLE = "circle";
    public const CLOUD = "cloud";
    public const COCKTAIL = "cocktail";
    public const COFFEE = "coffee";
    public const COG = "cog";
    public const COINS = "coins";
    public const COMPASS = "compass";
    public const COOKIE = "cookie";
    public const COUCH = "couch";
    public const CROW = "crow";
    public const CROWN = "crown";
    public const DICE = "dice";
    public const DOG = "dog";
    public const DOVE = "dove";
    public const DRAGON = "dragon";
    public const DRUM = "drum";
    public const EGG = "egg";
    public const EYE = "eye";
    public const FAN = "fan";
    public const FEATHER = "feather";
    public const FIRE = "fire";
    public const FISH = "fish";
    public const FLAG = "flag";
    public const FLUSHED = "flushed";
    public const FLY = "fly";
    public const FROG = "frog";
    public const FUTBOL = "futbol";
    public const GAMEPAD = "gamepad";
    public const GEM = "gem";
    public const GHOST = "ghost";
    public const GIFT = "gift";
    public const GLOBE = "globe";
    public const GRIMACE = "grimace";
    public const GRIN = "grin";
    public const GUITAR = "guitar";
    public const HAMBURGER = "hamburger";
    public const HAMMER = "hammer";
    public const HELICOPTER = "helicopter";
    public const HIKING = "hiking";
    public const HIPPO = "hippo";
    public const HOME = "home";
    public const HORSE = "horse";
    public const HOTDOG = "hotdog";
    public const INDUSTRY = "industry";
    public const KISS = "kiss";
    public const LAUGH = "laugh";
    public const LEAF = "leaf";
    public const LEMON = "lemon";
    public const LIGHTBULB = "lightbulb";
    public const METEOR = "meteor";
    public const MOBILE = "mobile";
    public const MOON = "moon";
    public const MOTORCYCLE = "motorcycle";
    public const MOUNTAIN = "mountain";
    public const MOUSE = "mouse";
    public const OTTER = "otter";
    public const PASTAFARIANISM = "pastafarianism";
    public const PAW = "paw";
    public const PEN = "pen";
    public const PLANE = "plane";
    public const SHIP = "ship";
    public const SMILE = "smile";
    public const SNOWFLAKE = "snowflake";
    public const SNOWMAN = "snowman";
    public const SPIDER = "spider";
    public const STAR = "star";
    public const SUN = "sun";
    public const SUSE = "suse";
    public const TOOTH = "tooth";
    public const TRACTOR = "tractor";
    public const TRAIN = "train";
    public const TRAM = "tram";
    public const TROPHY = "trophy";
    public const TRUCK = "truck";
    public const UMBRELLA = "umbrella";

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
