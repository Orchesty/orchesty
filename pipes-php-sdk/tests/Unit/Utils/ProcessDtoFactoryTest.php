<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\Utils;

use Hanaboso\PipesPhpSdk\Utils\ProcessDtoFactory;
use Hanaboso\Utils\String\Json;
use PipesPhpSdkTests\KernelTestCaseAbstract;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ProcessDtoFactoryTest
 *
 * @package PipesPhpSdkTests\Unit\Utils
 */
final class ProcessDtoFactoryTest extends KernelTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesPhpSdk\Utils\ProcessDtoFactory::createFromRequest
     * @covers \Hanaboso\PipesPhpSdk\Utils\ProcessDtoFactory::createDto
     */
    public function testCreateFromRequest(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            [],
            Json::encode([ProcessDtoFactory::BODY => 'aa', ProcessDtoFactory::HEADERS => []]),
        );

        $dto = ProcessDtoFactory::createFromRequest($request);
        self::assertEquals('aa', $dto->getData());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Utils\ProcessDtoFactory::createBatchFromRequest
     * @covers \Hanaboso\PipesPhpSdk\Utils\ProcessDtoFactory::createBatchDto
     */
    public function testCreateBatchFromRequest(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            [],
            Json::encode([ProcessDtoFactory::BODY => 'aa', ProcessDtoFactory::HEADERS => []]),
        );

        $dto = ProcessDtoFactory::createBatchFromRequest($request);
        self::assertEquals('aa', $dto->getData());
    }

}
