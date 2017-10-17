<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 10/4/17
 * Time: 5:28 PM
 */

namespace Tests\ApiEntity;

use CcApi\ApiEntity\SettingField;
use CcApi\ApiEntity\UserSystem;
use CcApi\ApiEntity\UserSystemFactory;
use PHPUnit\Framework\TestCase;

/**
 * Class UserSystemFactoryTest
 *
 * @package Tests\ApiEntity
 */
class UserSystemFactoryTest extends TestCase
{

    /**
     * @covers       SettingFieldFactory::create()
     * @dataProvider createDataProvider
     *
     * @param array      $data
     * @param UserSystem $assertSystem
     */
    public function testCreate(array $data, UserSystem $assertSystem): void
    {
        $system = UserSystemFactory::create($data);
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
                (new UserSystem()),
            ],
            [
                [
                    'key'            => 'key',
                    'type'           => 'type',
                    'name'           => 'name',
                    'description'    => 'desc',
                    'token'          => 'token',
                    'authorized'     => TRUE,
                    'synchronized'   => TRUE,
                    'unknown'        => 'unknown',
                    'setting_fields' => [
                        [
                            'key'      => 'key',
                            'type'     => 'type',
                            'value'    => 'value',
                            'label'    => 'label',
                            'required' => TRUE,
                            'unknown'  => 'unknown',
                        ],
                    ],
                ],
                (new UserSystem())
                    ->setKey('key')
                    ->setType('type')
                    ->setName('name')
                    ->setDescription('desc')
                    ->setToken('token')
                    ->setAuthorized(TRUE)
                    ->setSynchronized(TRUE)
                    ->addSettingField((new SettingField())
                        ->setKey('key')
                        ->setType('type')
                        ->setValue('value')
                        ->setLabel('label')
                        ->setRequired(TRUE)
                    ),
            ],
        ];
    }

}