<?php

namespace Tests;

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Environment;
use PHPUnit\Framework\TestCase;

/**
 * This is an example class that shows how you could set up a method that
 * runs the application. Note that it doesn't cover all use-cases and is
 * tuned to the specifics of this skeleton app, so if your needs are
 * different, you'll need to change it.
 */
class ClipartTestCase extends TestCase
{

    /** @test */
    public function clipartGetTest()
    {
        $a = 2;
        $b = 3;
        $c = $a * $b;
        $this->assertEquals($c, 44);
    }
}