<?php declare(strict_types=1);

namespace Tests\Integration\Logs;

use Elastica\Document;
use Exception;
use Hanaboso\CommonsBundle\Database\Document\Node;
use Hanaboso\CommonsBundle\Enum\TypeEnum;
use Hanaboso\CommonsBundle\Utils\DateTimeUtils;
use Hanaboso\MongoDataGrid\GridRequestDto;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class ElasticLogsTest
 *
 * @package Tests\Integration\Logs
 */
final class ElasticLogsTest extends DatabaseTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testGetData(): void
    {
        $this->prepareData();

        $result = self::$container->get('hbpf.elastic.logs')->getData(
            new GridRequestDto(
                [
                    'filter' => '{"severity":"ERROR"}',
                ]
            )
        );

        self::assertEquals(
            [
                'limit'  => 10,
                'offset' => 0,
                'count'  => 1,
                'total'  => 1,
                'items'  => [
                    [
                        'id'             => $result['items'][0]['id'],
                        'severity'       => 'ERROR',
                        'message'        => 'Message 5',
                        'type'           => 'starting_point',
                        'correlation_id' => 'Correlation ID 5',
                        'topology_id'    => 'Topology ID 5',
                        'topology_name'  => 'Topology Name 5',
                        'node_id'        => $result['items'][0]['node_id'],
                        'node_name'      => 'Node',
                        'timestamp'      => $result['items'][0]['timestamp'],
                    ],
                ],
            ],
            $result
        );
    }

    /**
     * @throws Exception
     */
    private function prepareData(): void
    {
        $client = self::$container->get('elastica.client');
        $index  = $client->getIndex('logstash');
        $index->create([], TRUE);

        $node = (new Node())->setType(TypeEnum::START)->setName('Node');
        $this->dm->persist($node);
        $this->dm->flush();

        for ($i = 1; $i <= 10; $i++) {
            $index->getType('_doc')->addDocument(
                new Document(
                    $i,
                    [
                        '@timestamp' => DateTimeUtils::getUtcDateTime(),
                        'version'    => sprintf('Version %s', $i),
                        'message'    => sprintf('Message %s', $i),
                        'host'       => sprintf('Host %s', $i),
                        'pipes'      => [
                            '@timestamp'     => DateTimeUtils::getUtcDateTime(),
                            'type'           => 'starting_point',
                            'hostname'       => sprintf('Hostname %s', $i),
                            'channel'        => sprintf('Channel %s', $i),
                            'severity'       => $i === 5 ? 'ERROR' : 'INFO',
                            'correlation_id' => sprintf('Correlation ID %s', $i),
                            'topology_id'    => sprintf('Topology ID %s', $i),
                            'topology_name'  => sprintf('Topology Name %s', $i),
                            'node_id'        => $node->getId(),
                            'node_name'      => sprintf('Node Name %s', $i),
                            'stacktrace'     => [
                                'message' => sprintf('Message %s', $i),
                                'class'   => sprintf('Class %s', $i),
                                'file'    => sprintf('File %s', $i),
                                'trace'   => sprintf('Trace %s', $i),
                                'code'    => sprintf('Code %s', $i),
                            ],
                        ],
                    ]
                )
            );
        }

        $index->refresh();
    }

}
