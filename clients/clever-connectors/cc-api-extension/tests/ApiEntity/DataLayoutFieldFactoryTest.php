<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 11.12.17
 * Time: 14:41
 */

namespace Tests\ApiEntity;

use CcApi\ApiEntity\DataLayoutField;
use CcApi\ApiEntity\DataLayoutFieldFactory;
use PHPUnit\Framework\TestCase;

/**
 * Class DataLayoutFieldFactoryTest
 *
 * @package Tests\ApiEntity
 */
class DataLayoutFieldFactoryTest extends TestCase
{

    /**
     * @covers       DataLayoutFieldFactory::create()
     * @dataProvider createDataProvider
     *
     * @param array           $data
     * @param DataLayoutField $assert
     */
    public function testCreate(array $data, DataLayoutField $assert): void
    {
        $this->assertEquals($assert, DataLayoutFieldFactory::create($data));
    }

    /**
     * @return array
     */
    public function createDataProvider(): array
    {
        return [
            [[], new DataLayoutField()],
            [
                ['key' => 'key', 'type' => 'type'],
                (new DataLayoutField())
                    ->setKey('key')
                    ->setType('type'),
            ],
        ];
    }

}