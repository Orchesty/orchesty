<?php declare(strict_types=1);

namespace Tests\Live\AppBundle\Model\Systems\Impl\Nutshell\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Nette\Utils\Json;
use React\EventLoop\Factory;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class NutshellSyncContactConnectorTest
 *
 * @package Tests\Live\AppBundle\Model\Systems\Impl\Nutshell\Connector
 */
final class NutshellSyncContactConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessBatch(): void
    {
        $this->markTestSkipped();
        $connector  = $this->container->get('hbpf.connector.nutshell-sync-contact-connector');
        $processDto = $this->prepareConnectorProcessDto([
            'username' => 'nutshell@mailinator.com',
            'api_key'  => '967b1f7b321e6305d18e6656a650c32420aba98d',
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