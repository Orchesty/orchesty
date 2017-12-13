<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Shoptet\Mapper;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Model\Systems\Impl\Shoptet\Mapper\ShoptetUpdatedCustomerMapper;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Nette\Utils\Json;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class ShoptetUpdatedCustomerMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Shoptet\Mapper
 */
class ShoptetUpdatedCustomerMapperTest extends ConnectorTestCaseAbstract
{

    /**
     * @covers ShoptetUpdatedCustomerMapper::process()
     */
    public function testProcess(): void
    {
        $response = Json::decode($this->getMapper()->process(
            (new ProcessDto())->setData(
                $this->getRequest('ShoptetCustomerForMapper.json')
            )->setHeaders([]))->getData(), TRUE
        );

        $this->assertEquals([
            CleverFieldsEnum::EMAIL      => 'abc@abc.com',
            CleverFieldsEnum::FIRST_NAME => 'asd',
            CleverFieldsEnum::LAST_NAME  => 'asddd',
            CleverFieldsEnum::FOREIGN_ID => '146278be-ca07-11e7-8216-002590dad85e',
            CleverFieldsEnum::REACTIVATE => TRUE,
            CleverFieldsEnum::SEND_OPTIN => FALSE,
            CleverFieldsEnum::LISTS      => [0 => 'list-123'],
        ], $response);
    }

    /**
     * @return ShoptetUpdatedCustomerMapper
     */
    private function getMapper(): ShoptetUpdatedCustomerMapper
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

        return new ShoptetUpdatedCustomerMapper($dm);
    }

}