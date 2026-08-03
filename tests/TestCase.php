<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        // PHP 8.5 deprecates several PDO/MySQL constants; the app code is unaffected,
        // so keep the test output clean by masking deprecation noise from the driver.
        error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

        parent::setUp();
    }
}
