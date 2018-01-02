<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Hubspot\Mapper;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\Mapper\HubspotCreatedContactMapper;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Nette\Utils\Json;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class HubspotCreatedContactMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Hubspot\Mapper
 */
final class HubspotCreatedContactMapperTest extends ConnectorTestCaseAbstract
{

    /**
     * @covers HubspotCreatedContactMapper::process()
     */
    public function testProcess(): void
    {
        $response = Json::decode(
            $this->getMapper()->process((new ProcessDto())
                ->setData($this->getRequest('HubspotCreatedContactMapper.json'))
                ->setHeaders([]))
                ->getData(),
            TRUE
        );

        $this->assertEquals([
            CleverFieldsEnum::EMAIL      => 'testingapis@hubspot.com',
            CleverFieldsEnum::FIRST_NAME => 'Codey',
            CleverFieldsEnum::LAST_NAME  => 'Huang',
            CleverFieldsEnum::FOREIGN_ID => '3234574',
            CleverFieldsEnum::REACTIVATE => TRUE,
            CleverFieldsEnum::SEND_OPTIN => FALSE,
            CleverFieldsEnum::LISTS      => [0 => 'list-123'],
        ], $response);
    }

    /**
     * @covers HubspotCreatedContactMapper::process()
     */
    public function testProcessFail(): void
    {
        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionCode(CleverConnectorsException::MISSING_DATA);

        $this->getMapper(FALSE)->process((new ProcessDto())->setData(json_encode([])))->getData();
    }

    /**
     * @covers HubspotCreatedContactMapper::process()
     */
    public function testProcessFail1(): void
    {
        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionCode(CleverConnectorsException::UNKNOWN_SUBSCRIPTION_TYPE);

        $data = [
            'subscriptionType' => '',
            'properties'       => [
                'email' => [
                    'value' => '',
                ],
            ],
        ];

        $this->getMapper(FALSE)->process((new ProcessDto())->setData(json_encode($data)))->getData();
    }

    /**
     *
     */
    public function testProcessFail2(): void
    {
        $data = [
            'vid'               => 123,
            'subscriptionType'  => 'contact.creation',
            'properties'        => [],
            'identity-profiles' => [
                [
                    'vid'        => 123,
                    'identities' => [
                        [
                            'type'  => 'EMAIL',
                            'value' => 'email@email.com',
                        ],
                    ],
                ],
            ],
        ];

        $this->getMapper()->process((new ProcessDto())->setData(json_encode($data))->setHeaders([]))->getData();

        unset($data['subscriptionType']);

        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionCode(CleverConnectorsException::MISSING_DATA);

        $this->getMapper(FALSE)->process((new ProcessDto())->setData(json_encode($data)))->getData();
    }

    /**
     *
     */
    public function testProcessFail3(): void
    {
        $data = [
            'vid'              => 123,
            'subscriptionType' => 'contact.propertyChange',
        ];

        $res        = $this->getMapper(FALSE)->process((new ProcessDto())->setData(json_encode($data))->setHeaders([]));
        $resultCode = $res->getHeader(CMHeaders::createKey(CMHeaders::RESULT_CODE));

        self::assertEquals(1003, $resultCode);
    }

    /**
     *
     */
    public function testProcessSetHeadersToStop(): void
    {
        $data = [
            'subscriptionType' => 'contact.deletion',
        ];

        $dto = (new ProcessDto())
            ->setData(json_encode($data))
            ->setHeaders([]);

        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionCode(CleverConnectorsException::DISALLOWED_SUBSCRIPTION_TYPE);

        $this->getMapper(FALSE)->process($dto);
    }

    /**
     * @param bool $list
     *
     * @return HubspotCreatedContactMapper|object
     */
    private function getMapper(bool $list = TRUE)
    {
        $systemInstall = new SystemInstall();
        $systemInstall->setSettings([SystemInstall::SELECT_LIST => 'list-123']);

        $systemInstallRepository = $this->createMock(SystemInstallRepository::class);

        if ($list) {
            $systemInstallRepository
                ->expects($this->at(0))
                ->method('getSystemInstallFromHeaders')
                ->willReturn($systemInstall);
        }

        /** @var MockObject|DocumentManager $dm */
        $dm = $this->createMock(DocumentManager::class);
        $dm
            ->expects($this->at(0))
            ->method('getRepository')
            ->willReturn($systemInstallRepository);

        return new HubspotCreatedContactMapper($dm);
    }

}