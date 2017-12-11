<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 11.12.17
 * Time: 14:42
 */

namespace Tests\ApiEntity;

use CcApi\ApiEntity\MapTemplateField;
use CcApi\ApiEntity\MapTemplateFieldFactory;
use PHPUnit\Framework\TestCase;

/**
 * Class MapTemplateFieldTest
 *
 * @package Tests\ApiEntity
 */
class MapTemplateFieldTest extends TestCase
{

    /**
     * @covers       MapTemplateFieldFactory::create()
     * @dataProvider createDataProvider
     *
     * @param array            $data
     * @param MapTemplateField $assert
     */
    public function testCreate(array $data, MapTemplateField $assert): void
    {
        $this->assertEquals($assert, MapTemplateFieldFactory::create($data));
    }

    /**
     * @return array
     */
    public function createDataProvider(): array
    {
        return [
            [[], new MapTemplateField()],
            [
                [
                    'name'  => 'name',
                    'type'  => 'type',
                    'items' => ['item1', 'item2'],
                ],
                (new MapTemplateField())
                    ->setName('name')
                    ->setType('type')
                    ->addItem('item1')
                    ->addItem('item2'),
            ],
        ];
    }

}