<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Hubspot\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\Mapper\HubspotUpdateContactMapper;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class HubspotUpdateContactMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Hubspot\Mapper
 */
class HubspotUpdateContactMapperTest extends ConnectorTestCaseAbstract
{

    /**
     * @var HubspotUpdateContactMapper|object
     */
    private $mapper;

    /**
     * @covers HubspotUpdateContactMapper::process()
     */
    public function testProcess(): void
    {
        $response = Json::decode(
            $this->getMapper()
                ->process((new ProcessDto())->setData($this->getRequest('HubspotUpdateContactMapper.json')))
                ->getData(),
            TRUE
        );

        $this->assertEquals([
            CleverFieldsEnum::EMAIL      => 'testingapis@hubspot.com',
            CleverFieldsEnum::FIRST_NAME => 'Codey',
            CleverFieldsEnum::LAST_NAME  => 'Huang',
            CleverFieldsEnum::FOREIGN_ID => 3234574,
            CleverFieldsEnum::REACTIVATE => TRUE,
        ], $response);
    }

    /**
     * @covers HubspotUpdateContactMapper::process()
     */
    public function testProcessFail(): void
    {
        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionCode(CleverConnectorsException::MISSING_DATA);

        $this->getMapper()->process((new ProcessDto())->setData(json_encode([])))->getData();

        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionCode(CleverConnectorsException::MISSING_DATA);

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
            'subscriptionType' => '',
            'properties'       => [
                'email' => [
                    'value' => '',
                ],
            ],
        ];

        $this->getMapper()->process((new ProcessDto())->setData(json_encode($data)))->getData();

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
            'properties'       => [
                'email' => [],
            ],
        ];

        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionCode(CleverConnectorsException::MISSING_DATA);

        $this->getMapper()->process((new ProcessDto())->setData(json_encode($data)))->getData();
    }

    /**
     *
     */
    public function testProcessSetHeadersToStop(): void
    {
        $data = [
            'subscriptionType' => 'contact.deletion',
            'properties'       => [
                'email' => [
                    'value' => '',
                ],
            ],
        ];

        $dto        = (new ProcessDto())
            ->setData(json_encode($data))
            ->setHeaders([CMHeaders::createKey(CMHeaders::RESULT_CODE) => '0']);
        $res        = $this->getMapper()->process($dto);
        $resultCode = $res->getHeader(CMHeaders::createKey(CMHeaders::RESULT_CODE));

        self::assertEquals(1003, $resultCode);
    }

    /**
     * @return HubspotUpdateContactMapper|object
     */
    private function getMapper()
    {
        if (!$this->mapper) {
            return $this->container->get('hbpf.custom_node.hubspot-update-contact-mapper');
        }

        return $this->mapper;
    }

}