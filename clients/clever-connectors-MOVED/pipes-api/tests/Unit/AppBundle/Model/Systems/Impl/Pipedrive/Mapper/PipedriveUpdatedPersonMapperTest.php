<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Pipedrive\Mapper;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\Mapper\PipedriveUpdatedPersonMapper;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Nette\Utils\Json;
use PHPUnit_Framework_MockObject_MockObject;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class PipedriveUpdatePersonMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Pipedrive\Mapper
 */
final class PipedriveUpdatedPersonMapperTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessEventUpdated(): void
    {
        $sys = new SystemInstall();
        $sys->setSettings([SystemInstall::SELECT_LIST => 'someList']);

        $repo = $this->createMock(SystemInstallRepository::class);
        $repo->method('getSystemInstallFromHeaders')->willReturn($sys);

        /** @var DocumentManager|PHPUnit_Framework_MockObject_MockObject $dm */
        $dm = $this->createMock(DocumentManager::class);
        $dm->method('getRepository')->willReturn($repo);

        $connector = new PipedriveUpdatedPersonMapper($dm);

        $response = Json::decode(
            $connector->process((new ProcessDto())->setData($this->getRequest('personUpdateWebhook.json'))
                ->setHeaders([]))
                ->getData(),
            TRUE
        );

        $this->assertEquals([
            CleverFieldsEnum::EMAIL      => 'asd@asd.com',
            CleverFieldsEnum::FIRST_NAME => 'asddd',
            CleverFieldsEnum::FOREIGN_ID => '1',
            CleverFieldsEnum::REACTIVATE => TRUE,
            CleverFieldsEnum::SEND_OPTIN => FALSE,
        ], $response);
    }

}