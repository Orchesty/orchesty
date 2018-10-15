<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Salesforce\Mapper;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Model\Systems\Impl\Salesforce\Mapper\SalesforceCreatedContactMapper;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Nette\Utils\Json;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class SalesforceCreatedContactMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Salesforce\Mapper
 */
final class SalesforceCreatedContactMapperTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessEvent(): void
    {
        $systemInstall = new SystemInstall();
        $systemInstall->setSettings([SystemInstall::SELECT_LIST => 'list-123']);

        $systemInstallRepository = $this->createMock(SystemInstallRepository::class);
        $systemInstallRepository
            ->expects($this->at(0))
            ->method('getSystemInstallFromHeaders')
            ->willReturn($systemInstall);

        /** @var MockObject|DocumentManager $dm */
        $dm = $this->createMock(DocumentManager::class);
        $dm
            ->expects($this->at(0))
            ->method('getRepository')
            ->willReturn($systemInstallRepository);

        $connector = new SalesforceCreatedContactMapper($dm);
        $response  = Json::decode($connector->process(
            (new ProcessDto())->setData($this->getRequest('SalesforceCreatedContactMapper.json'))->setHeaders([]))
            ->getData(),
            TRUE
        );

        $this->assertEquals([
            CleverFieldsEnum::EMAIL      => 'email@example.com',
            CleverFieldsEnum::FIRST_NAME => 'First Name',
            CleverFieldsEnum::LAST_NAME  => 'Last Name',
            CleverFieldsEnum::FOREIGN_ID => '123456789',
            CleverFieldsEnum::REACTIVATE => TRUE,
            CleverFieldsEnum::SEND_OPTIN => FALSE,
            CleverFieldsEnum::LISTS      => [0 => 'list-123'],
        ], $response);
    }

}