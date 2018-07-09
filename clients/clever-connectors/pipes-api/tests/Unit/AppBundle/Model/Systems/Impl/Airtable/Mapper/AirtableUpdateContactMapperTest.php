<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Airtable\Mapper;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class AirtableUpdateContactMapper
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Airtable\Mapper
 */
final class AirtableUpdateContactMapperTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessEvent(): void
    {
        $connector = $this->ownContainer->get('hbpf.custom_node.airtable-update-contact-mapper');

        $response = Json::decode($connector->process(
            (new ProcessDto())
                ->setData(Json::encode(
                    [
                        '_foreign_id' => 'abc',
                    ]
                ))->addHeader(CMHeaders::createKey(CMHeaders::CM_EVENT_TYPE), SystemInstall::EVENT_UNSUBSCRIBE))
            ->getData(),
            TRUE
        );

        $this->assertEquals([
            '_foreign_id' => 'abc',
            'unsubscribe' => 1,
        ], $response);
    }

    /**
     *
     */
    public function testProcessEventBadRequest(): void
    {
        $connector = $this->ownContainer->get('hbpf.custom_node.airtable-update-contact-mapper');

        $this->expectException(CleverConnectorsException::class);
        $this->expectExceptionCode(CleverConnectorsException::MISSING_DATA);

        $connector->process((new ProcessDto())->setData('{}'))->getData();
    }

}