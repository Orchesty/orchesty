<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Salesforce;

use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class SalesForceContactMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Salesforce
 */
class SalesForceContactMapperTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessEvent(): void
    {
        $connector = $this->container->get('hbpf.custom_node.salesforce-contact-mapper');

        $response = Json::decode(
            $connector->process((new ProcessDto())->setData(file_get_contents(__DIR__ . '/data/SalesForceSingleContactItem.json')))
                ->getData(),
            TRUE
        );

        $this->assertEquals([
            'email'      => 'gfdhg@asd.com',
            'first_name' => 'bmnmb',
            'last_name'  => 'zzzz',
        ], $response);
    }

}