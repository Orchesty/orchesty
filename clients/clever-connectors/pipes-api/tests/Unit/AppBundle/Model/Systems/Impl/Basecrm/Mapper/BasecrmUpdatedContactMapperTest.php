<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Basecrm\Mapper;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class BasecrmUpdatedContactMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Basecrm\Mapper
 */
final class BasecrmUpdatedContactMapperTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testMapper(): void
    {
        $node = $this->container->get('hbpf.custom_node.basecrm-updated-contact-mapper');

        $response = Json::decode($node->process(
            (new ProcessDto())->setData(
                $this->getRequest('contactCreated.json')
            ))->getData(), TRUE
        );

        $expt = [
            'email'       => 'eml@eml.com',
            'first_name'  => 'first',
            '_foreign_id' => '188442396',
            'reactivate'  => TRUE,
            'send_optin'  => FALSE,
            'last_name'   => 'last',
        ];

        self::assertEquals($expt, $response);
    }

}