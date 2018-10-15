<?php declare(strict_types=1);

namespace Tests\Live\AppBundle\Model\Systems\Impl\Magento2\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Nette\Utils\Json;
use React\EventLoop\Factory;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class Magento2SyncCustomerConnectorTest
 *
 * @package Tests\Live\AppBundle\Model\Systems\Impl\Magento2Old\Connector
 */
final class Magento2SyncCustomerConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessBatch(): void
    {
        $this->markTestSkipped();
        $connector  = $this->container->get('hbpf.connector.magento2-sync-customer-connector');
        $processDto = $this->prepareConnectorProcessDto([
            'system_url'   => 'http://magento21.lab.hanaboso.net',
            'user_name'    => 'user',
            'password'     => 'bitnami1',
            'access_token' => 'w8bt94v43c9e7sr48fnmi548w679yjgv',
        ]);

        $loop = Factory::create();
        $connector->processBatch($processDto, $loop, function (SuccessMessage $message): void {
            $this->assertTrue(is_array(Json::decode($message->getData(), TRUE)));
        })->then(
            function (): void {
                $this->assertTrue(TRUE);
            },
            function (): void {
                $this->assertTrue(FALSE);
            }
        )->done();

        $loop->run();
        $this->dm->clear();

        $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($processDto->getHeaders());
        $this->assertInstanceOf(SystemInstall::class, $systemInstall);
        $this->assertTrue($systemInstall->isSynchronized());
    }

}