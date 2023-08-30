<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\Logs;

use PipesFrameworkTests\DatabaseTestCaseAbstract;

/**
 * Class ElasticClientFactoryTest
 *
 * @package PipesFrameworkTests\Integration\Logs
 */
final class ElasticClientFactoryTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\Logs\ElasticaClientFactory::create
     */
    public function testCreate(): void
    {
        $client = self::getContainer()->get('elastica.client');
        $config = (array) $client->getConfig();

        self::assertEquals(
            [
                [
                    'host' => 'elasticsearch',
                    'port' => 9_200,
                ],
            ],
            $config['servers'],
        );
    }

}
