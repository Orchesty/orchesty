<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Basecrm\Mapper;

use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class BasecrmDeleteContactMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Basecrm\Mapper
 */
final class BasecrmDeleteContactMapperTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testMapper(): void
    {
        $node = $this->container->get('hbpf.custom_node.basecrm-delete-contact-mapper');

        $response = Json::decode($node->process(
            (new ProcessDto())->setData(
                $this->getRequest('contactItemDeleted.json')
            ))->getData(), TRUE
        );

        $expt = [
            'email'       => '',
            '_foreign_id' => '187643117',
            'reactivate'  => FALSE,
        ];

        self::assertEquals($expt, $response);
    }

}