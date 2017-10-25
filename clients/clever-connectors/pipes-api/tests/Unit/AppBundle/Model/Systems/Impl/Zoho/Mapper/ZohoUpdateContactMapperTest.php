<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Zoho\Mapper;

use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class ZohoUpdateContactMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Zoho\Mapper
 */
final class ZohoUpdateContactMapperTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcess(): void
    {
        $node = $this->container->get('hbpf.custom_node.zoho-update-contact-mapper');

        $response = json_decode(
            $node->process((new ProcessDto())->setData($this->getRequest('singleContact.json')))
                ->getData(),
            TRUE
        );

        $expt = [
            'email'       => 'john-buttbenton@gmail.com',
            'first_name'  => 'John',
            'last_name'   => 'Butt',
            '_foreign_id' => '85896000000078213',
            'reactivate'  => TRUE,
        ];

        self::assertEquals($expt, $response);
    }

}