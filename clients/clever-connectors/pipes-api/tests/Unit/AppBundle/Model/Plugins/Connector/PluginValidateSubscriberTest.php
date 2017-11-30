<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Plugins\Connector;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Plugins\Connector\PluginCreatedSubscriberConnector;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Tests\KernelTestCaseAbstract;

/**
 * Class PluginValidateSubscriberTest
 *
 * @package Tests\Unit\AppBundle\Model\Plugins\Connector
 */
final class PluginValidateSubscriberTest extends KernelTestCaseAbstract
{

    /**
     * @covers PluginValidateSubscriber::processAction()
     */
    public function testMissingData(): void
    {
        $conn = new PluginCreatedSubscriberConnector();
        $dto  = new ProcessDto();
        $dto->setHeaders([])->setData('');

        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionCode(CleverConnectorsException::MISSING_DATA);
        $conn->processAction($dto);
    }

    /**
     * @covers PluginValidateSubscriber::processAction()
     */
    public function testWrongFormatData(): void
    {
        $conn = new PluginCreatedSubscriberConnector();
        $dto  = new ProcessDto();
        $dto->setHeaders([])->setData('asd');

        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionCode(CleverConnectorsException::MISSING_DATA);
        $conn->processAction($dto);
    }

    /**
     * @covers PluginValidateSubscriber::processAction()
     */
    public function testProcessAction(): void
    {
        $conn = new PluginCreatedSubscriberConnector();
        $dto  = new ProcessDto();
        $dto->setHeaders([])->setData(json_encode([
            '_foreign_id'       => 'de9f2c7fd25e1b3afad3e85a0bd17d9b100db4b3',
            'email'             => 'doiufoaosr',
            'first_name'        => 'doiufoaosr',
            'last_name'         => 'doiufoaosr',
            'send_optin'        => FALSE,
            'distribution_list' => '5ff42ca6-1965-49ed-97d0-b2b568c88bfd',
        ]));

        $res = $conn->processAction($dto);

        self::assertEquals(json_encode([
            'email'       => 'doiufoaosr',
            'reactivate'  => TRUE,
            'send_optin'  => FALSE,
            'first_name'  => 'doiufoaosr',
            'last_name'   => 'doiufoaosr',
            '_foreign_id' => 'de9f2c7fd25e1b3afad3e85a0bd17d9b100db4b3',
            'lists'       => ['5ff42ca6-1965-49ed-97d0-b2b568c88bfd'],
        ]), $res->getData());
    }

}