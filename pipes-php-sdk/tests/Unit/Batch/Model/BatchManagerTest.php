<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\Batch\Model;

use Exception;
use Hanaboso\PipesPhpSdk\Batch\BatchAbstract;
use Hanaboso\PipesPhpSdk\Batch\Model\BatchManager;
use Hanaboso\PipesPhpSdk\Utils\ProcessDtoFactory;
use Hanaboso\Utils\String\Json;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesPhpSdkTests\KernelTestCaseAbstract;
use PipesPhpSdkTests\Unit\HbPFBatchBundle\Loader\NullBatch;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class BatchManagerTest
 *
 * @package PipesPhpSdkTests\Unit\Batch\Model
 */
#[CoversClass(BatchManager::class)]
#[CoversClass(BatchAbstract::class)]
final class BatchManagerTest extends KernelTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        /** @var BatchManager $manager */
        $manager = self::getContainer()->get('hbpf.manager.batch');

        /** @var NullBatch $batch */
        $batch = self::getContainer()->get('hbpf.batch.null');
        $dto   = $manager->processAction(
            $batch,
            new Request(content: Json::encode([ProcessDtoFactory::BODY => '', ProcessDtoFactory::HEADERS => []])),
        );
        self::assertSame('[]', $dto->getBridgeData());
    }

}
