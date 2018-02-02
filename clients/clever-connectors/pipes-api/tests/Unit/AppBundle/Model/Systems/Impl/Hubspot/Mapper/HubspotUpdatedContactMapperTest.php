<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Hubspot\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\Mapper\HubspotUpdatedContactMapper;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class HubspotUpdatedContactMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Hubspot\Mapper
 */
final class HubspotUpdatedContactMapperTest extends ConnectorTestCaseAbstract
{

    /**
     * @var HubspotUpdatedContactMapper
     */
    private $mapper;

    /**
     * @covers HubspotUpdatedContactMapper::process()
     */
    public function testProcess(): void
    {
        $response = Json::decode(
            $this->getMapper()
                ->process((new ProcessDto())
                    ->setData($this->getRequest('HubspotUpdatedContactMapper.json'))
                    ->setHeaders([]))
                ->getData(),
            TRUE
        );

        $this->assertEquals([
            CleverFieldsEnum::EMAIL      => 'testingapis@hubspot.com',
            CleverFieldsEnum::FIRST_NAME => 'Codey',
            CleverFieldsEnum::LAST_NAME  => 'Huang',
            CleverFieldsEnum::FOREIGN_ID => 3234574,
            CleverFieldsEnum::REACTIVATE => TRUE,
            CleverFieldsEnum::SEND_OPTIN => FALSE,
        ], $response);
    }

    /**
     * @covers HubspotUpdatedContactMapper::process()
     */
    public function testProcessFail(): void
    {
        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionCode(CleverConnectorsException::MISSING_DATA);

        $this->getMapper()->process((new ProcessDto())->setData(json_encode([])))->getData();
    }

    /**
     * @covers HubspotUpdatedContactMapper::process()
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

        $this->getMapper()->process((new ProcessDto())->setData(json_encode($data)))->getData();
    }

    /**
     *
     */
    public function testProcessFail2(): void
    {
        $data = [
            'vid'               => 123,
            'subscriptionType'  => 'contact.propertyChange',
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

        $this->getMapper()->process((new ProcessDto())->setData(json_encode($data)))->getData();
    }

    /**
     *
     */
    public function testProcessFail3(): void
    {
        $data = [
            'subscriptionType' => '',
        ];

        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionCode(CleverConnectorsException::UNKNOWN_SUBSCRIPTION_TYPE);

        $this->getMapper()->process((new ProcessDto())->setData(json_encode($data))->setHeaders([]));
    }

    /**
     *
     */
    public function testProcessFail4(): void
    {
        $data = [
            'subscriptionType' => 'contact.deletion',
        ];

        $dto = (new ProcessDto())
            ->setData(json_encode($data))
            ->setHeaders([CMHeaders::createKey(CMHeaders::RESULT_CODE) => '0']);

        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionCode(CleverConnectorsException::DISALLOWED_SUBSCRIPTION_TYPE);

        $this->getMapper()->process($dto);
    }

    /**
     *
     */
    public function testProcessSetHeadersToStop(): void
    {
        $data = [
            'subscriptionType' => 'contact.creation',
        ];

        $dto        = (new ProcessDto())
            ->setData(json_encode($data))
            ->setHeaders([CMHeaders::createKey(CMHeaders::RESULT_CODE) => '0']);
        $res        = $this->getMapper()->process($dto);
        $resultCode = $res->getHeader(CMHeaders::createKey(CMHeaders::RESULT_CODE));

        self::assertEquals(1003, $resultCode);
    }

    /**
     * @return HubspotUpdatedContactMapper
     */
    private function getMapper(): HubspotUpdatedContactMapper
    {
        if (!$this->mapper) {
            return $this->container->get('hbpf.custom_node.hubspot-updated-contact-mapper');
        }

        return $this->mapper;
    }

}