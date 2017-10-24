<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Zoho\Mapper;

use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class ZohoDeleteContactMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Zoho\Mapper
 */
final class ZohoDeleteContactMapperTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcess(): void
    {
        $node = $this->container->get('hbpf.custom_node.zoho-delete-contact-mapper');

        $response = json_decode(
            $node->process((new ProcessDto())->setData($this->getRequest('singleDeletedContactId.json')))
                ->getData(),
            TRUE
        );

        $expt = [
            'email'       => '',
            '_foreign_id' => '85896000000078215',
            'reactivate'  => FALSE,
        ];

        self::assertEquals($expt, $response);
    }

}