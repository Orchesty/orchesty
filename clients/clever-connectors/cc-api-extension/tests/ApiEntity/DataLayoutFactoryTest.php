<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 11.12.17
 * Time: 14:40
 */

namespace Tests\ApiEntity;

use CcApi\ApiEntity\DataLayout;
use CcApi\ApiEntity\DataLayoutFactory;
use CcApi\ApiEntity\DataLayoutField;
use PHPUnit\Framework\TestCase;

/**
 * Class DataLayoutFactoryTest
 *
 * @package Tests\ApiEntity
 */
class DataLayoutFactoryTest extends TestCase
{

    /**
     * @covers       DataLayoutFactory::create()
     * @dataProvider createDataProvider
     *
     * @param array      $data
     * @param DataLayout $assert
     */
    public function testCreate(array $data, DataLayout $assert): void
    {
        $this->assertEquals($assert, DataLayoutFactory::create($data));
    }

    /**
     * @return array
     */
    public function createDataProvider(): array
    {
        return [
            [[], new DataLayout()],
            [
                ['_id' => '1', 'action' => 'action', 'fields' => [['key' => 'key', 'type' => 'type']]],
                (new DataLayout())
                    ->setId('1')
                    ->setAction('action')
                    ->addField(
                        (new DataLayoutField())
                            ->setKey('key')
                            ->setType('type')
                    ),
            ],
        ];
    }

}