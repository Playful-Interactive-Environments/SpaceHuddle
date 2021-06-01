<?php

namespace App\Domain\Service;

/**
 * Holds the settings for the whole application.
 * @package PieLab\GAB\Config
 */
class Settings
{
    /**
     * Holds all the settings.
     * @var array
     */
    private array $settings;

    /**
     * Holds the application's single instance.
     * @var Settings
     */
    private static Settings $instance;

    /**
     * Settings constructor. Creates the internal settings array that contains the application's configuration
     * parameters
     */
    private function __construct()
    {
        $this->settings = [
            "errorReporting" => E_ALL,
            "timezone" => "Europe/Vienna",
            "token" => [
                "expiresAfter" => "PT24H" // See https://www.php.net/manual/de/dateinterval.construct.php
            ]
        ];
    }

    /**
     * Returns the one instance of the class.
     * @return static The Settings instance.
     */
    public static function getInstance(): static
    {
        if (empty(self::$instance)) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    /**
     * Returns a settings-entry by its key. If the key is left out, the whole array is returned.
     * @param string $key The key for the settings entry.
     * @return mixed The values associated with the settings key or the complete array.
     */
    public function get(string $key = ""): mixed
    {
        return (empty($key)) ? $this->settings : $this->settings[$key];
    }
}
