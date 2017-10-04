<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 10/4/17
 * Time: 5:25 PM
 */

namespace Tests\ApiEntity;

use CcApi\ApiEntity\System;
use CcApi\ApiEntity\SystemFactory;
use PHPUnit\Framework\TestCase;

/**
 * Class SystemFactoryTest
 *
 * @package Tests\ApiEntity
 */
class SystemFactoryTest extends TestCase
{

    /**
     * @covers       SettingFieldFactory::create()
     * @dataProvider createDataProvider
     *
     * @param array  $data
     * @param System $assertSystem
     */
    public function testCreate(array $data, System $assertSystem): void
    {
        $system = SystemFactory::create($data);
        $this->assertEquals($assertSystem, $system);
    }

    /**
     * @return array
     */
    public function createDataProvider(): array
    {
        return [
            [
                [],
                (new System()),
            ],
            [
                [
                    'key'         => 'key',
                    'type'        => 'type',
                    'name'        => 'name',
                    'description' => 'desc',
                    'unknown'     => 'unknown',
                ],
                (new System())
                    ->setKey('key')
                    ->setType('type')
                    ->setName('name')
                    ->setDescription('desc'),
            ],
        ];
    }

}