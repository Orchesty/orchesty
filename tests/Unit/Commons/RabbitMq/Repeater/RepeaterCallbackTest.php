<?php
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 30.8.17
 * Time: 20:30
 */

namespace Tests\Unit\Commons\RabbitMq\Repeater;

use Hanaboso\PipesFramevork\Commons\Repeater\RepeaterCallback;
use PHPUnit\Framework\TestCase;

class RepeaterCallbackTest extends TestCase
{

    /**
     * @dataProvider handle
     * @covers       RepeaterCallback::handle()
     */
    public function testHandle()
    {
        $this->markTestIncomplete('after callback implementation');
        $this->assertTrue(TRUE);
    }

    /**
     * @return array
     */
    public function handle(): array
    {
        return [

        ];
    }

}