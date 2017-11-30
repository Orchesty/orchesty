<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Basecrm\Mapper;

use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
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
                $this->getRequest('contactItem.json')
            ))->getData(), TRUE
        );

        $expt = [
            'email'       => 'asd@asd.com',
            'first_name'  => 'Base',
            '_foreign_id' => '187596661',
            'reactivate'  => TRUE,
            'send_optin'  => FALSE,
        ];

        self::assertEquals($expt, $response);
    }

}