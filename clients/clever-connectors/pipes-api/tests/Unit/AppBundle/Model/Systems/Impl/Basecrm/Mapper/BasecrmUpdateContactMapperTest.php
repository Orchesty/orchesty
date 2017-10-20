<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Basecrm\Mapper;

use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class BasecrmUpdateContactMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Basecrm\Mapper
 */
class BasecrmUpdateContactMapperTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testMapper(): void
    {
        $node = $this->container->get('hbpf.custom_node.basecrm-update-contact-mapper');

        $response = Json::decode($node->process(
            (new ProcessDto())->setData(
                $this->getRequest('contactItem.json')
            ))->getData(), TRUE
        );

        $expt = [
            'email'       => 'asd@asd.com',
            'first_name'  => 'Base',
            'last_name'   => '',
            '_foreign_id' => '187596661',
            'reactivate'  => TRUE,
        ];

        self::assertEquals($expt, $response);
    }

}