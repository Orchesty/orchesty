<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Shopify\Mapper;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Model\Systems\Impl\Shopify\Mapper\ShopifyCreatedCustomerMapper;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Nette\Utils\Json;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class ShopifyCreatedCustomerMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Shopify\Mapper
 */
final class ShopifyCreatedCustomerMapperTest extends ConnectorTestCaseAbstract
{

    /**
     * @covers ShopifyCreatedCustomerMapper::process()
     */
    public function testProcess(): void
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

        $connector = new ShopifyCreatedCustomerMapper($dm);
        $response  = Json::decode(
            $connector->process((new ProcessDto())
                ->setData($this->getRequest('ShopifyUpdatedCustomerMapper.json'))
                ->setHeaders([]))
                ->getData(),
            TRUE
        );

        $this->assertEquals([
            CleverFieldsEnum::EMAIL      => 'email@example.com',
            CleverFieldsEnum::FIRST_NAME => 'First',
            CleverFieldsEnum::LAST_NAME  => 'Last',
            CleverFieldsEnum::FOREIGN_ID => '129715699742',
            CleverFieldsEnum::REACTIVATE => TRUE,
            CleverFieldsEnum::SEND_OPTIN => FALSE,
            CleverFieldsEnum::LISTS      => [0 => 'list-123'],
        ], $response);
    }

}