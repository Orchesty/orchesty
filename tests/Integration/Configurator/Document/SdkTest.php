<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\Configurator\Document;

use Exception;
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
     * @covers \Hanaboso\PipesFramework\Configurator\Document\Sdk::getName
     * @covers \Hanaboso\PipesFramework\Configurator\Document\Sdk::getHeaders
     * @covers \Hanaboso\PipesFramework\Configurator\Document\Sdk::setHeaders
     * @covers \Hanaboso\PipesFramework\Configurator\Document\Sdk::setName
     * @covers \Hanaboso\PipesFramework\Configurator\Document\Sdk::getUrl
     * @covers \Hanaboso\PipesFramework\Configurator\Document\Sdk::setUrl
     * @covers \Hanaboso\PipesFramework\Configurator\Document\Sdk::toArray
     *
     * @throws Exception
     */
    public function testDocument(): void
    {
        $sdk = (new Sdk())->setUrl('value')->setName('key')->setHeaders([]);
        $this->pfd($sdk);

        self::assertEquals('value', $sdk->getUrl());
        self::assertEquals('key', $sdk->getName());
        self::assertEquals(
            [
                'id'    => $sdk->getId(),
                'name'   => 'key',
                'url' => 'value',
                'headers' => [],
            ],
            $sdk->toArray(),
        );
    }

}
