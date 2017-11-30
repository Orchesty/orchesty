<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Hubspot\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\Mapper\HubspotCreatedContactMapper;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class HubspotCreatedContactMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Hubspot\Mapper
 */
final class HubspotCreatedContactMapperTest extends ConnectorTestCaseAbstract
{

    /**
     * @var HubspotCreatedContactMapper|object
     */
    private $mapper;

    /**
     * @covers HubspotCreatedContactMapper::process()
     */
    public function testProcess(): void
    {
        $response = Json::decode(
            $this->getMapper()
                ->process((new ProcessDto())
                    ->setData($this->getRequest('HubspotCreatedContactMapper.json'))
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
     * @covers HubspotCreatedContactMapper::process()
     */
    public function testProcessFail(): void
    {
        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionCode(CleverConnectorsException::MISSING_DATA);

        $this->getMapper()->process((new ProcessDto())->setData(json_encode([])))->getData();
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

        $this->getMapper()->process((new ProcessDto())->setData(json_encode($data)))->getData();
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

        $this->getMapper()->process((new ProcessDto())->setData(json_encode($data)))->getData();
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

        $res        = $this->getMapper()->process((new ProcessDto())->setData(json_encode($data))->setHeaders([]));
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

        $this->getMapper()->process($dto);
    }

    /**
     * @return HubspotCreatedContactMapper|object
     */
    private function getMapper()
    {
        if (!$this->mapper) {
            return $this->container->get('hbpf.custom_node.hubspot-created-contact-mapper');
        }

        return $this->mapper;
    }

}