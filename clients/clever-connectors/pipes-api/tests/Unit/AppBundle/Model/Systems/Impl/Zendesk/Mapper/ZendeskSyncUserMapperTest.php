<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Zendesk\Mapper;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Model\Systems\Impl\Zendesk\Mapper\ZendeskSyncUserMapper;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Nette\Utils\Json;
use PHPUnit_Framework_MockObject_MockObject;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class ZendeskSyncUserMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Zendesk\Mapper
 */
final class ZendeskSyncUserMapperTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessEvent(): void
    {
        $sys = new SystemInstall();
        $sys->setSettings([
            'list' => 'someList',
        ]);

        $repo = $this->createMock(SystemInstallRepository::class);
        $repo->method('getSystemInstallFromHeaders')->willReturn($sys);

        /** @var DocumentManager|PHPUnit_Framework_MockObject_MockObject $dm */
        $dm = $this->createMock(DocumentManager::class);
        $dm->method('getRepository')->willReturn($repo);

        $connector = new ZendeskSyncUserMapper($dm);

        $response = Json::decode(
            $connector->process((new ProcessDto())->setData($this->getRequest('singleItemSync.json'))->setHeaders([]))
                ->getData(),
            TRUE
        );

        $this->assertEquals([
            CleverFieldsEnum::EMAIL      => 'customer@example.com',
            CleverFieldsEnum::FIRST_NAME => 'Sample',
            CleverFieldsEnum::LAST_NAME  => 'customer',
            CleverFieldsEnum::FOREIGN_ID => '115316687153',
            CleverFieldsEnum::REACTIVATE => TRUE,
            CleverFieldsEnum::SEND_OPTIN => FALSE,
            CleverFieldsEnum::LISTS      => ['someList'],
        ], $response);
    }

}