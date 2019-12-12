<?php declare(strict_types=1);

namespace Tests\Unit\HbPFJoinerBundle\Handler;

use Exception;
use Hanaboso\PipesPhpSdk\HbPFJoinerBundle\Exception\JoinerException;
use Tests\KernelTestCaseAbstract;

/**
 * Class JoinerHandlerTest
 *
 * @package Tests\Unit\HbPFJoinerBundle\Handler
 */
final class JoinerHandlerTest extends KernelTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFJoinerBundle\Handler\JoinerHandler::processJoiner()
     * @throws Exception
     */
    public function testJoin(): void
    {
        $handler = self::$container->get('hbpf.handler.joiner');

        $data = [
            'data' => [],
        ];

        self::expectException(JoinerException::class);
        self::expectExceptionCode(JoinerException::MISSING_DATA_IN_REQUEST);
        $handler->processJoinerTest('null', $data);

        $data['count'] = 3;
        $handler->processJoiner('null', $data);
    }

}
