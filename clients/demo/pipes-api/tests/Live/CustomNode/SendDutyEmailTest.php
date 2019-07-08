<?php declare(strict_types=1);

namespace Tests\Live\CustomNode;

use EmailServiceBundle\Exception\MailerException;
use Hanaboso\CommonsBundle\Exception\DateTimeException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;
use ReflectionException;
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
     * @throws MailerException
     * @throws DateTimeException
     * @throws CurlException
     * @throws ConnectorException
     * @throws ReflectionException
     */
    public function testSend(): void
    {
        $sendDutyEmail = self::$container->get('hbpf.custom_node.send-duty-email');
        $data = $sendDutyEmail->process(new ProcessDto())->getData();
        self::assertIsString($data);
    }

}