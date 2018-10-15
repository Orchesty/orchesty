<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: michal.bartl
 * Date: 10/25/17
 * Time: 3:26 PM
 */

namespace Tests\Live\AppBundle\Model\Systems\Impl\Quickbooks\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Hanaboso\CommonsBundle\Crypt\CryptManager;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Nette\Utils\Json;
use React\EventLoop\Factory;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class QuickbooksSyncCustomerConnectorTest
 *
 * @package Tests\Live\AppBundle\Model\Systems\Impl\Quickbooks\Connector
 */
class QuickbooksSyncCustomerConnectorTest extends DatabaseTestCaseAbstract
{

    /**
     * @return void
     */
    public function testProcessBatch(): void
    {
        $this->markTestSkipped();
        /** @var SuccessMessage $result */
        $result    = NULL;
        $connector = $this->container->get('hbpf.connector.quickbooks-sync-customer-connector');

        $topology = (new Topology())->setName('Topology');
        $this->persistAndFlush($topology);

        $settings = [
            'realmId'      => '193514649567854',
            'access_token' => 'eyJlbmMiOiJBMTI4Q0JDLUhTMjU2IiwiYWxnIjoiZGlyIn0..UoS5d29_t1LLW-fjkk0yTw.EmH5jFzhXERm8ipN-QmD0bbduPv7bhN1hOkuMwnoSIwFJuZ3mKjAYjJIECCCpWaWmtJnlrtATnoOXjCv8hFRIrGhrLqnnJv7zeZ5xTTNkItW1E454SvX9ZahCTxH2OVBLvzxP8dH3HK5_JYKoE_6IknNTTMawkviTPAXptJ7uJNvTzKXCSfny41ZzD2jKl8VXTuDaJp4oYOCjkab_4sBz5Aip6JaIZQPzUDUTAYP-t3MEmreL9Lm-c4DTfD_MsJlJzFd6gyDBq2563Edl4fh7bFBJ6NNOC-K51vvHIsRiGaDOnhftY7eP6dCuqBbQOeajxYVdHNd17iMs5hI3sVILcKQpHduOPpspPhNj8I6XYtRJR11UhUphoXz2YFP0kGuyrtd0qCD3g1hABxUr8QkQF0vwJnLV1FWgjMJTPpX1B9ZKpzqYVD7yCwt4NMTOPu8gaoSigKa55z0dSAHSurfBA6b9Ts6cJfV6jqtSRmSOmBRhryqR_Siz6l5Mzjgj-oG_LLoTiycYBoFHFfqsAe-GKt7EuarwjYgHEqz4XjMI-xrmT3LRudwzjaceDriBLG-JW3E2qm_nDPYdAgJbTMr_Ae3257EIr96FmuM-EnLehDui4LKLWSEuu_QrEEZS3vOkoGyxkmbTRNhBBiL7Nfh7Kb55sU0dmXZUmXk6GXotMyzTka8P64s1t2TEAIT3Drk.ahYLyUaq-V2aH-s5DSNlAQ',
        ];

        $system = new SystemInstall();
        $system
            ->setUser('u_123')
            ->setToken('t-456')
            ->setSystem('s_-666')
            ->setSettings($settings);

        $this->persistAndFlush($system);

        $dtoData = [
            'data' => [
                'system_install' => [
                    '_id'               => $system->getId(),
                    'user'              => $system->getUser(),
                    'token'             => $system->getToken(),
                    'system'            => $system->getSystem(),
                    'encryptedSettings' => CryptManager::encrypt($settings),
                ],
                'topology'       => ['name' => 'top-name-ever'],
            ],
        ];

        $node = (new Node())
            ->setName('Node')
            ->setTopology($topology->getId());
        $this->persistAndFlush($node);

        $processDto = (new ProcessDto())->setData(Json::encode($dtoData))->setHeaders([
            CMHeaders::createKey(CMHeaders::TOKEN)      => 't-456',
            CMHeaders::createKey(CMHeaders::GUID)       => 'u_123',
            CMHeaders::createKey(CMHeaders::SYSTEM_KEY) => 's_-666',
        ]);

        $loop = Factory::create();

        $process = $connector->processBatch($processDto, $loop,
            function (SuccessMessage $message) use (&$result): void {
                $result = $message;
            });
        $process->done();

        $loop->run();

        $this->assertInstanceOf(SuccessMessage::class, $result);
        $data = json_decode($result->getData());
        $this->assertTrue(is_array($data));

        $this->dm->clear();
        /** @var SystemInstall $sys */
        $sys = $this->dm->getRepository(SystemInstall::class)->find($system->getId());
        $this->assertInstanceOf(SystemInstall::class, $sys);
        $this->assertTrue($sys->isSynchronized());
    }

}