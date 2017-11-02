<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Nutshell\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Utils\PipesHeaders;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class NutshellDeletedContactMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Nutshell\Mapper
 */
final class NutshellDeletedContactMapperTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcess(): void
    {
        $connector = $this->container->get('hbpf.custom_node.nutshell-deleted-contact-mapper');

        $response = Json::decode($connector->process(
            (new ProcessDto())->setData(
                str_replace('"create"', '"delete"', $this->getRequest('NutshellWebhookResponse.json'))
            ))->getData(), TRUE
        );

        $this->assertEquals([
            CleverFieldsEnum::EMAIL       => 'User01@User01.com',
            CleverFieldsEnum::FIRST_NAME  => 'User01',
            CleverFieldsEnum::LAST_NAME   => 'User01',
            CleverFieldsEnum::FOREIGN_ID  => '1',
            CleverFieldsEnum::REACTIVATE  => TRUE,
        ], $response);
    }

    /**
     *
     */
    public function testProcessInvalid(): void
    {
        $connector = $this->container->get('hbpf.custom_node.nutshell-deleted-contact-mapper');

        $dto = $connector->process(
            (new ProcessDto())->setData(
                $this->getRequest('NutshellWebhookResponse.json')
            )->setHeaders([])
        );

        $this->assertEquals([
            PipesHeaders::createKey(PipesHeaders::RESULT_CODE)    => 1003,
            PipesHeaders::createKey(PipesHeaders::RESULT_STATUS)  => 'DO_NOT_CONTINUE',
            PipesHeaders::createKey(PipesHeaders::RESULT_MESSAGE) => 'Data does not contains contact delete event',
            PipesHeaders::createKey(PipesHeaders::RESULT_DETAIL)  => '',
        ], $dto->getHeaders());
    }

}