<?php declare(strict_types=1);

namespace Tests\Unit\HbPFJoinerBundle\Handler;

use Exception;
use Hanaboso\PipesFramework\HbPFJoinerBundle\Exception\JoinerException;
use Tests\KernelTestCaseAbstract;

/**
 * Class JoinerHandlerTest
 *
 * @package Tests\Unit\HbPFJoinerBundle\Handler
 */
final class JoinerHandlerTest extends KernelTestCaseAbstract
{

    /**
     * @covers JoinerHandler::processJoiner()
     * @throws Exception
     */
    public function testJoin(): void
    {
        $handler = $this->ownContainer->get('hbpf.handler.joiner');

        $data = [
            'data' => [],
        ];

        $this->expectException(JoinerException::class);
        $this->expectExceptionCode(JoinerException::MISSING_DATA_IN_REQUEST);
        $handler->processJoinerTest('null', $data);

        $data['count'] = 3;
        $handler->processJoiner('null', $data);
    }

}
