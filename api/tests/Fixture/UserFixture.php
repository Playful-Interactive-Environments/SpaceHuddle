<?php

namespace App\Test\Fixture;

/**
 * Fixture.
 */
class UserFixture
{
    /** @var string Table name */
    public $table = "user";

    /**
     * Records.
     *
     * @var array<string, mixed> Records
     */
    public $records = [
        [
            "id" => "1",
            "username" => "admin",
            "password" => '$2y$10$8SCHkI4JUKJ2NA353BTHW.Kgi33HI.2C35xd/j5YUzBx05F1O4lJO'
        ],
        [
            "id" => "2",
            "username" => "user",
            "password" => '$1$X64.UA0.$kCSxRsj3GKk7Bwy3P6xn1.'
        ],
    ];
}
