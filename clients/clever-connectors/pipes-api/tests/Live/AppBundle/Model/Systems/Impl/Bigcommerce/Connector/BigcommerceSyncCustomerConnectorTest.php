<?php declare(strict_types=1);

namespace Tests\Live\AppBundle\Model\Systems\Impl\Bigcommerce\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Nette\Utils\Json;
use React\EventLoop\Factory;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class BigcommerceSyncCustomerConnectorTest
 *
 * @package Tests\Live\AppBundle\Model\Systems\Impl\Bigcommerce\Connector
 */
final class BigcommerceSyncCustomerConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessBatch(): void
    {
        $this->markTestSkipped();
        $connector  = $this->container->get('hbpf.connector.bigcommerce-sync-customer-connector');
        $processDto = $this->prepareConnectorProcessDto([
            'store_id'     => 'ukcfcghi',
            'client_id'    => 'p7f4o1hfl1zdkz1bp1sy7u8qs0fq7q',
            'access_token' => '7ndpkdbqb0h1wycrxhtw43ye0yprtn9',
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