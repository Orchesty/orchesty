<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: lukas.hlavac
 * Date: 10/13/17
 * Time: 9:53 AM
 */

namespace Tests\Unit\AppBundle\Model\CustomNode;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CustomNode\ProgressIncrementer;
use CleverConnectors\AppBundle\Model\ProgressCounter\ProgressCounterService;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;

/**
 * Class StartingProgressTest
 *
 * @package Tests\Unit\AppBundle\Model\CustomNode
 */
final class ProgressIncrementerTest extends TestCase
{

    /**
     * @covers StartingProgress::process()
     */
    public function testProcess(): void
    {
        /** @var PHPUnit_Framework_MockObject_MockObject|ProgressCounterService $progressCounterService */
        $progressCounterService = $this->getMockBuilder(ProgressCounterService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $progressCounterService
            ->expects($this->once())
            ->method('increment')
            ->with('abc');
        
        $dto = new ProcessDto();
        $dto
            ->setData(json_encode(['data' => []]))
            ->setHeaders([CMHeaders::createKey(CMHeaders::PROCESS_ID) => 'abc']);

        $startingProgress = new ProgressIncrementer($progressCounterService);
        $startingProgress->process($dto);
    }

    /**
     * @covers StartingProgress::process()
     */
    public function testProcessFail(): void
    {
        /** @var PHPUnit_Framework_MockObject_MockObject|ProgressCounterService $progressCounterService */
        $progressCounterService = $this->getMockBuilder(ProgressCounterService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dto = new ProcessDto();
        $dto
            ->setData(json_encode(['data' => []]))
            ->setHeaders([]);

        self::expectException(CleverConnectorsException::class);
        self::expectExceptionCode(CleverConnectorsException::PROCESS_ID_NOT_FOUND);

        $startingProgress = new ProgressIncrementer($progressCounterService);
        $startingProgress->process($dto);
    }

}