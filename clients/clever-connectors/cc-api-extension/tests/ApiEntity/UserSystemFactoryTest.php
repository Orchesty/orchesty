<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 10/4/17
 * Time: 5:28 PM
 */

namespace Tests\ApiEntity;

use CcApi\ApiEntity\DataLayout;
use CcApi\ApiEntity\MapTemplate;
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
                    'auth_type'      => 'oauth',
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
                    'actions'        => ['action1', 'action2'],
                    'data_layouts'   => [
                        ['_id' => '1', 'action' => 'action'],
                    ],
                    'map_templates'  => [
                        ['_id' => '1', 'action' => 'action', 'direction' => 'in'],
                    ],
                ],
                (new UserSystem())
                    ->setKey('key')
                    ->setType('type')
                    ->setName('name')
                    ->setAuthType('oauth')
                    ->setDescription('desc')
                    ->setToken('token')
                    ->setAuthorized(TRUE)
                    ->setSynchronized(TRUE)
                    ->addSettingField(
                        (new SettingField())
                            ->setKey('key')
                            ->setType('type')
                            ->setValue('value')
                            ->setLabel('label')
                            ->setRequired(TRUE)
                    )->setActions(['action1', 'action2'])
                    ->addDataLayout(
                        (new DataLayout())
                            ->setId('1')
                            ->setAction('action')
                    )
                    ->addMapTemplate(
                        (new MapTemplate())
                            ->setId('1')
                            ->setAction('action')
                            ->setDirection('in')
                    ),
            ],
        ];
    }

}