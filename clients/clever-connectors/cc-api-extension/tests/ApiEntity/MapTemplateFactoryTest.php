<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 11.12.17
 * Time: 14:37
 */

namespace Tests\ApiEntity;

use CcApi\ApiEntity\MapTemplate;
use CcApi\ApiEntity\MapTemplateFactory;
use CcApi\ApiEntity\MapTemplateField;
use PHPUnit\Framework\TestCase;

/**
 * Class MapTemplateFactoryTest
 *
 * @package Tests\ApiEntity
 */
class MapTemplateFactoryTest extends TestCase
{

    /**
     * @covers       MapTemplateFactory::create()
     * @dataProvider createDataProvider
     *
     * @param array       $data
     * @param MapTemplate $assert
     */
    public function testCreate(array $data, MapTemplate $assert): void
    {
        $this->assertEquals($assert, MapTemplateFactory::create($data));
    }

    /**
     * @return array
     */
    public function createDataProvider(): array
    {
        return [
            [[], new MapTemplate()],
            [
                [
                    '_id'       => '1',
                    'action'    => 'action',
                    'direction' => 'in',
                    'fields'    => [
                        ['name' => 'name', 'type' => 'type', 'items' => ['xxx']],
                    ],
                ],
                (new MapTemplate())
                    ->setId('1')
                    ->setAction('action')
                    ->setDirection('in')->addField(
                        (new MapTemplateField())
                            ->setName('name')
                            ->setType('type')
                            ->addItem('xxx')
                    ),
            ],
        ];
    }

}