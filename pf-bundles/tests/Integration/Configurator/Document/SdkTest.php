<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\Configurator\Document;

use Exception;
use Hanaboso\PipesFramework\Configurator\Document\Sdk;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkTests\DatabaseTestCaseAbstract;

/**
 * Class SdkTest
 *
 * @package PipesFrameworkTests\Integration\Configurator\Document
 */
#[CoversClass(Sdk::class)]
final class SdkTest extends DatabaseTestCaseAbstract
{

    /**
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
                'headers' => [],
                'id'    => $sdk->getId(),
                'name'   => 'key',
                'url' => 'value',
            ],
            $sdk->toArray(),
        );
    }

}
