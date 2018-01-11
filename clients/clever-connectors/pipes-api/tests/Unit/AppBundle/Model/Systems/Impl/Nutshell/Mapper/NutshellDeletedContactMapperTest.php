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
        $data      = Json::decode(str_replace(
            '"create"',
            '"delete"',
            $this->getRequest('NutshellWebhookResponse.json')),
            TRUE
        );

        $response = Json::decode($connector->process($this->prepareConnectorProcessDto([
            'username' => 'nutshell@mailinator.com',
            'api_key'  => '967b1f7b321e6305d18e6656a650c32420aba98d',
            'list'     => '4b04d334-3db9-b290-d0aa-099642329856',
        ], $data, [], TRUE))->getData(), TRUE);

        $this->assertEquals([
            CleverFieldsEnum::EMAIL      => 'User01@User01.com',
            CleverFieldsEnum::FIRST_NAME => 'User01',
            CleverFieldsEnum::LAST_NAME  => 'User01',
            CleverFieldsEnum::FOREIGN_ID => '1',
            CleverFieldsEnum::REACTIVATE => TRUE,
            CleverFieldsEnum::SEND_OPTIN => FALSE,
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
            PipesHeaders::createKey(PipesHeaders::RESULT_MESSAGE) => 'Data does not contains contact delete event',
            PipesHeaders::createKey(PipesHeaders::RESULT_DETAIL)  => '',
        ], $dto->getHeaders());
    }

}