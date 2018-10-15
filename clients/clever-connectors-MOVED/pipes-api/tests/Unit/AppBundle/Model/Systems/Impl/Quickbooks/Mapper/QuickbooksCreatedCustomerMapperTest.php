<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Quickbooks\Mapper;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Model\Systems\Impl\Quickbooks\Mapper\QuickbooksCreatedCustomerMapper;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Nette\Utils\Json;
use PHPUnit_Framework_MockObject_MockObject;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class QuickbooksCreatedCustomerMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Quickbooks\Mapper
 */
final class QuickbooksCreatedCustomerMapperTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcess(): void
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

        $connector = new QuickbooksCreatedCustomerMapper($dm);

        $response = Json::decode(
            $connector->process((new ProcessDto())->setData($this->getRequest('QuickbooksCustomerMapper.json'))
                ->setHeaders([]))
                ->getData(),
            TRUE
        );

        $this->assertEquals([
            CleverFieldsEnum::EMAIL      => 'jdrew2@myemail.com',
            CleverFieldsEnum::FIRST_NAME => 'James2',
            CleverFieldsEnum::LAST_NAME  => 'King2',
            CleverFieldsEnum::SEND_OPTIN => FALSE,
            CleverFieldsEnum::FOREIGN_ID => '2',
            CleverFieldsEnum::REACTIVATE => TRUE,
            CleverFieldsEnum::LISTS      => ['someList'],
        ], $response);
    }

}