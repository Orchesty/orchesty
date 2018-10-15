<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Nutshell\Mapper;

use CleverConnectors\AppBundle\Model\CM\SubscriberConnector\SubscriberObject\CMSubscriber;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class NutshellCreateContactMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Nutshell\Mapper
 */
final class NutshellCreateContactMapperTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcess(): void
    {
        $connector = $this->ownContainer->get('hbpf.custom_node.nutshell-create-contact-mapper');

        $response = Json::decode($connector->process((new ProcessDto())->setData(Json::encode(
            (new CMSubscriber())
                ->setEmail('User01@User01.com')
                ->setFirstName('User01')
                ->setLastName('User01')
                ->toArray()
        )))->getData(), TRUE);

        $this->assertEquals([
            'jsonrpc' => '2.0',
            'id'      => 'contact',
            'method'  => 'newContact',
            'params'  => [
                'contact' => [
                    'name'  => [
                        'givenName'  => 'User01',
                        'familyName' => 'User01',
                    ],
                    'email' => ['User01@User01.com'],
                ],
            ],
        ], $response);
    }

}