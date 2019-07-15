<?php declare(strict_types=1);

namespace Tests\Live\CustomNode;

use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class SendDutyEmailTest
 *
 * @package Tests\Live\CustomNode
 */
class SendDutyEmailTest extends KernelTestCase
{

    /**
     *
     */
    protected function setUp(): void
    {
        self::bootKernel();
    }

    /**
     * @throws Exception
     */
    public function testSend(): void
    {
        $sendDutyEmail = self::$container->get('hbpf.custom_node.send-duty-email');
        $data          = $sendDutyEmail->process(new ProcessDto())->getData();
        self::assertIsString($data);
    }

}
