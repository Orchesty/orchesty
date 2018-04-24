<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: stanislav.kundrat
 * Date: 10/13/17
 * Time: 9:53 AM
 */

namespace Tests\Unit\AppBundle\Model\CustomNode;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CustomNode\StartingProgress;
use CleverConnectors\AppBundle\Model\ProgressCounter\ProgressCounterService;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class StartingProgressTest
 *
 * @package Tests\Unit\AppBundle\Model\CustomNode
 */
final class StartingProgressTest extends TestCase
{

    /**
     * @var MockObject|ProgressCounterService
     */
    private $progressCounterService;

    /**
     *
     */
    public function setUp(): void
    {
        $this->progressCounterService = $this->getMockBuilder(ProgressCounterService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->progressCounterService->method('start');
    }

    /**
     * @covers StartingProgress::process()
     */
    public function testProcess(): void
    {
        $dto = new ProcessDto();
        $dto
            ->setData(json_encode(['data' => []]))
            ->setHeaders([CMHeaders::createKey(CMHeaders::PROCESS_ID) => 'abc']);

        $startingProgress = new StartingProgress($this->progressCounterService);
        $startingProgress->process($dto);
    }

    /**
     * @covers StartingProgress::process()
     */
    public function testProcessFail(): void
    {
        $dto = new ProcessDto();
        $dto
            ->setData(json_encode(['data' => []]))
            ->setHeaders([]);

        self::expectException(CleverConnectorsException::class);
        self::expectExceptionCode(CleverConnectorsException::PROCESS_ID_NOT_FOUND);

        $startingProgress = new StartingProgress($this->progressCounterService);
        $startingProgress->process($dto);
    }

}