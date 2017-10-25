<?php
/**
 * Created by PhpStorm.
 * User: michal.bartl
 * Date: 10/25/17
 * Time: 3:26 PM
 */

namespace Tests\Integration\AppBundle\Model\Systems\Impl\Quickbooks\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Hanaboso\PipesFramework\Commons\Crypt\CryptManager;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Nette\Utils\Json;
use React\EventLoop\Factory;
use Tests\DatabaseTestCaseAbstract;

class QuickbooksSyncCustomerConnectorTest extends DatabaseTestCaseAbstract
{

    /**
     * @return void
     */
    public function testProcessBatch(): void
    {
        /** @var SuccessMessage $result */
        $result = null;
        $connector = $this->container->get('hbpf.connector.quickbooks-sync-customer-connector');

        $topology = (new Topology())->setName('Topology');
        $this->persistAndFlush($topology);

        $settings = [
            'realm_id'     => '193514649567854',
            'access_token' => 'eyJlbmMiOiJBMTI4Q0JDLUhTMjU2IiwiYWxnIjoiZGlyIn0..N-WXXzg39LOTdhtBoLN5bw.ujyfWacM-yYvPl0BNtfE3tKKjMveTypywy_Tl_-zVMBaKebNDGubVrqxfxjkRMHCTfgsqprd7Rq6RrwaALoFcooaYRwp-wP1tdd_j4s6smnk-oiKOriDkxQw1aaZ7YAkpakXouoh_jSqvoIerrLFvIwszMt0mHjCrYhZvmAlhBIKrE_3ufVn1zqyRbj6NJMCB23VWUIHXW_Ro0HnghP_LolgAYxNR6KgH1Fq1RyFwzWhmU_b5rN0g346NKpcMycSCGP1OcFNc4ZotXbxi17fkhH2R7rEK-X7tVEAWY5P6kzinraGm3H6DxlJnZUYMcTjFH258Ch5XjvmR0xPIw5C5jEJMM05Zer-jY0E2vn3q3UM-o4scbbcAuurbfpI44oE5qbAH6X-FvvuoNnsZWv5ac5CQoyw3YsdVZuVdSCBZBEhLjgnub863uYw9y7a9y-PpNW_mkMGAnKvlMWW0dTShGqUBo7y40CSU8sPLqVg_3b6QLo5W4NvFdQhJtWSjlTirntxcbWGYfn1uZmkyVs4cX3y9cTXxJeEySEnP_tD1-Sg48qMsY08N0W0Bc5UCijO3wTRZtwpHfS860MilMMj6ChUYMmWN7iBHojyj7iYUw1hBe1qXVrFNwTk52KbVyqWreJ5s2BZs53zcNWxkfK1wvl8ZEl1ZtRycZLP9N_0Nv9GPdpeyhw8IBFNAjsLMFowrIOkjU8zYkgty8rdtJ8lkbUpmVAm-3zWOnq72uv8OaU.ArHhLT-DVNH-QLCLCQT48g',
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

        $process = $connector->processBatch($processDto, $loop, function (SuccessMessage $message) use (&$result): void {
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