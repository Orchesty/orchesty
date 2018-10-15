<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Hubspot\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\Mapper\HubspotDeletedContactMapper;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class HubspotDeletedContactMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Hubspot\Mapper
 */
final class HubspotDeletedContactMapperTest extends ConnectorTestCaseAbstract
{

    /**
     * @var HubspotDeletedContactMapper
     */
    private $mapper;

    /**
     * @covers HubspotDeletedContactMapper::process()
     */
    public function testProcess(): void
    {
        $dto = (new ProcessDto())
            ->setData($this->getRequest('HubspotDeletedContactMapper.json'))
            ->setHeaders([CMHeaders::createKey(CMHeaders::RESULT_CODE) => '0']);

        $res      = $this->getMapper()->process($dto);
        $response = Json::decode($res->getData(), TRUE);

        $this->assertEquals([
            CleverFieldsEnum::EMAIL      => 1246965,
            CleverFieldsEnum::FOREIGN_ID => 1246965,
            CleverFieldsEnum::REACTIVATE => FALSE,
            CleverFieldsEnum::SEND_OPTIN => FALSE,
        ], $response);

        $resultCode = $res->getHeader(CMHeaders::createKey(CMHeaders::RESULT_CODE));

        self::assertEquals(0, $resultCode);
    }

    /**
     * @covers HubspotDeletedContactMapper::process()
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
            'objectId'         => 123,
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
            'objectId'         => 123,
        ];

        $headers = [];

        $dto = (new ProcessDto())
            ->setData(json_encode($data))
            ->setHeaders($headers);

        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionCode(CleverConnectorsException::UNKNOWN_SUBSCRIPTION_TYPE);

        $this->getMapper()->process($dto)->getData();
    }

    /**
     *
     */
    public function testProcessFail2_2(): void
    {
        $data = [
            'objectId' => 123,
        ];

        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionCode(CleverConnectorsException::MISSING_DATA);

        $this->getMapper()->process((new ProcessDto())->setData(json_encode($data))->setHeaders([]))->getData();
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

        $this->getMapper()->process((new ProcessDto())->setData(json_encode($data))->setHeaders([]))->getData();
    }

    /**
     *
     */
    public function testProcessSetHeadersToStop(): void
    {
        $data = [
            'subscriptionType' => 'contact.creation',
            'objectId'         => 123,
        ];

        $dto        = (new ProcessDto())
            ->setData(json_encode($data))
            ->setHeaders([CMHeaders::createKey(CMHeaders::RESULT_CODE) => '0']);
        $res        = $this->getMapper()->process($dto);
        $resultCode = $res->getHeader(CMHeaders::createKey(CMHeaders::RESULT_CODE));

        self::assertEquals(1003, $resultCode);
    }

    /**
     *
     */
    public function testProcessSetHeadersToStop2(): void
    {
        $data = [
            'subscriptionType' => 'contact.propertyChange',
            'objectId'         => 123,
        ];

        $dto        = (new ProcessDto())
            ->setData(json_encode($data))
            ->setHeaders([CMHeaders::createKey(CMHeaders::RESULT_CODE) => '0']);
        $res        = $this->getMapper()->process($dto);
        $resultCode = $res->getHeader(CMHeaders::createKey(CMHeaders::RESULT_CODE));

        self::assertEquals(1003, $resultCode);
    }

    /**
     * @return HubspotDeletedContactMapper
     */
    private function getMapper(): HubspotDeletedContactMapper
    {
        if (!$this->mapper) {
            return $this->ownContainer->get('hbpf.custom_node.hubspot-deleted-contact-mapper');
        }

        return $this->mapper;
    }

}