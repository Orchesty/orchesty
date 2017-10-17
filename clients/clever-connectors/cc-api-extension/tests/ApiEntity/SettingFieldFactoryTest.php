<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 10/4/17
 * Time: 5:16 PM
 */

namespace Tests\ApiEntity;

use CcApi\ApiEntity\SettingField;
use CcApi\ApiEntity\SettingFieldFactory;
use PHPUnit\Framework\TestCase;

/**
 * Class SettingFieldFactoryTest
 *
 * @package Tests\ApiEntity
 */
class SettingFieldFactoryTest extends TestCase
{

    /**
     * @covers       SettingFieldFactory::create()
     * @dataProvider createDataProvider
     *
     * @param array        $data
     * @param SettingField $field
     */
    public function testCreate(array $data, SettingField $field): void
    {
        $settingField = SettingFieldFactory::create($data);
        $this->assertEquals($field, $settingField);
    }

    /**
     * @return array
     */
    public function createDataProvider(): array
    {
        return [
            [
                [],
                (new SettingField()),
            ],
            [
                [
                    'key'            => 'key',
                    'type'           => 'type',
                    'value'          => 'value',
                    'label'          => 'label',
                    'required'       => TRUE,
                    'unknown'        => 'unknown',
                ],
                (new SettingField())
                    ->setKey('key')
                    ->setType('type')
                    ->setValue('value')
                    ->setLabel('label')
                    ->setRequired(TRUE),
            ],
        ];
    }

}