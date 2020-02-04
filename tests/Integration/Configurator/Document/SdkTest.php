<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\Configurator\Document;

use Hanaboso\PipesFramework\Configurator\Document\Sdk;
use PipesFrameworkTests\DatabaseTestCaseAbstract;

/**
 * Class SdkTest
 *
 * @package PipesFrameworkTests\Integration\Configurator\Document
 */
final class SdkTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Document\Sdk::getKey
     * @covers \Hanaboso\PipesFramework\Configurator\Document\Sdk::setKey
     * @covers \Hanaboso\PipesFramework\Configurator\Document\Sdk::getValue
     * @covers \Hanaboso\PipesFramework\Configurator\Document\Sdk::setValue
     * @covers \Hanaboso\PipesFramework\Configurator\Document\Sdk::toArray
     */
    public function testDocument(): void
    {
        $sdk = (new Sdk())->setValue('value')->setKey('key');

        self::assertEquals('value', $sdk->getValue());
        self::assertEquals('key', $sdk->getKey());
        self::assertEquals(
            [
                'id'    => NULL,
                'key'   => 'key',
                'value' => 'value',
            ],
            $sdk->toArray()
        );
    }

}
