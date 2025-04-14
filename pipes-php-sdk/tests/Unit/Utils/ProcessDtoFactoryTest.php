<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\Utils;

use Hanaboso\PipesPhpSdk\Utils\ProcessDtoFactory;
use Hanaboso\Utils\String\Json;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesPhpSdkTests\KernelTestCaseAbstract;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ProcessDtoFactoryTest
 *
 * @package PipesPhpSdkTests\Unit\Utils
 */
#[CoversClass(ProcessDtoFactory::class)]
final class ProcessDtoFactoryTest extends KernelTestCaseAbstract
{

    /**
     * @return void
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
        self::assertSame('aa', $dto->getData());
    }

    /**
     * @return void
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
        self::assertSame('aa', $dto->getData());
    }

}
