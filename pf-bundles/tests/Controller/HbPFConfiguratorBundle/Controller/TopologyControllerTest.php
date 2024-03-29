<?php declare(strict_types=1);

namespace PipesFrameworkTests\Controller\HbPFConfiguratorBundle\Controller;

use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Exception;
use Hanaboso\CommonsBundle\Exception\CronException;
use Hanaboso\CommonsBundle\Exception\NodeException;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Configurator\Document\ApiToken;
use Hanaboso\PipesFramework\Configurator\Enum\ApiTokenScopesEnum;
use Hanaboso\PipesFramework\Configurator\Exception\TopologyException;
use Hanaboso\PipesFramework\Database\Document\Node;
use Hanaboso\PipesFramework\Database\Document\Topology;
use Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler;
use Hanaboso\Utils\File\File;
use Hanaboso\Utils\String\Json;
use LogicException;
use PipesFrameworkTests\ControllerTestCaseAbstract;
use Throwable;

/**
 * Class TopologyControllerTest
 *
 * @package PipesFrameworkTests\Controller\HbPFConfiguratorBundle\Controller
 *
 * @covers  \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController
 * @covers  \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler
 * @covers  \Hanaboso\PipesFramework\Configurator\Model\TopologyManager
 */
final class TopologyControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @return void
     * @throws MongoDBException
     * @throws Exception
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->dm->getRepository(ApiToken::class)->clear();
        $this->dm->persist(
            (new ApiToken())
                ->setUser(ApplicationController::SYSTEM_USER)
                ->setScopes(ApiTokenScopesEnum::cases())
                ->setKey('1'),
        );
        $this->dm->flush();
    }

    /**
     * @throws Exception
     */
    public function testGetTopologies(): void
    {
        $this->createTopologies(4);
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/Topology/getTopologiesRequest.json',
            ['_id' => '5e32b04ce99e002a011e0d25'],
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::runTopologiesAction
     *
     * @throws Exception
     */
    public function testRunTopologies(): void
    {
        $topology = $this->createTopologies()[0];
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/Topology/runTopologiesRequest.json',
            ['message' => "Topology with key '63591c7d47e53c5324074268' not found!"],
            [':id' => $topology->getId()],
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::runTopologiesAction
     *
     * @throws Exception
     */
    public function testRunTopologiesErr(): void
    {
        $topology = $this->createTopologies()[0];
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/Topology/runTopologiesErrRequest.json',
            [],
            [':id' => $topology->getId()],
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::getTopologiesAction
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::getTopologies
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::getTopologyData
     *
     * @throws Exception
     */
    public function testGetTopologiesErr(): void
    {
        $this->createTopologies(4);

        $nodeHandlerMock = self::createMock(TopologyHandler::class);
        $nodeHandlerMock
            ->method('getTopologies')
            ->willThrowException(new MongoDBException());
        $container = $this->client->getContainer();
        $container->set('hbpf.configurator.handler.topology', $nodeHandlerMock);

        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/Topology/getTopologiesErrRequest.json',
            ['_id' => '5e32b04ce99e002a011e0d25'],
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::getTopologyAction
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::getTopology
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::getTopologyById
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::getTopologyData
     *
     * @throws Exception
     */
    public function testGetTopology(): void
    {
        $topology = $this->createTopologies()[0];

        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/Topology/getTopologyRequest.json',
            ['_id' => '5e32b3de8602642bb3758653'],
            [':id' => $topology->getId()],
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::getTopologyAction
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::getTopology
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::getTopologyById
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::getTopologyData
     *
     * @throws Exception
     */
    public function testGetTopologyNotFound(): void
    {
        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/Topology/getTopologyNotFoundRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::createTopologyAction
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::createTopology
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::getTopologyData
     *
     * @throws Exception
     */
    public function testCreateTopology(): void
    {
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/Topology/createTopologyRequest.json',
            ['_id' => '5e32b547b7c6da4b0c54ffc3'],
        );
    }

    /**
     * @covers       \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::createTopologyAction
     * @covers       \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::createTopology
     *
     * @dataProvider topologyErrorProvider
     *
     * @param Exception $exception
     *
     * @throws Exception
     */
    public function testCreateTopologyError(Exception $exception): void
    {
        $han = $this->createPartialMock(TopologyHandler::class, ['createTopology']);
        $han->method('createTopology')->willThrowException($exception);
        self::getContainer()->set('hbpf.configurator.handler.topology', $han);
        switch ($exception->getCode()) {
            case TopologyException::TOPOLOGY_NODE_NAME_NOT_FOUND:
            case TopologyException::TOPOLOGY_NODE_TYPE_NOT_FOUND:
                $this->assertResponseLogged(
                    $this->jwt,
                    __DIR__ . '/data/Topology/createTopology404Request.json',
                    [
                        'error_code' => 404,
                    ],
                );

                break;
            case TopologyException::TOPOLOGY_NODE_TYPE_NOT_EXIST:
            case TopologyException::SCHEMA_START_NODE_MISSING:
                $this->assertResponseLogged(
                    $this->jwt,
                    __DIR__ . '/data/Topology/createTopology400Request.json',
                    [
                        'error_code' => 400,
                    ],
                );

                break;
            default:
                $this->assertResponseLogged(
                    $this->jwt,
                    __DIR__ . '/data/Topology/createTopology500Request.json',
                    [
                        'error_code' => 500,
                    ],
                );
        }
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::updateTopologyAction
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::updateTopology
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::getTopologyById
     *
     * @throws Exception
     */
    public function testUpdateTopology(): void
    {
        $topology = (new Topology())
            ->setName('Topology')
            ->setDescr('Topology');
        $this->pfd($topology);

        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/Topology/updateTopologyRequest.json',
            ['_id' => '5e32bc7423cab649c23d4913'],
            [':id' => $topology->getId()],
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::updateTopologyAction
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::updateTopology
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::getTopologyById
     *
     * @throws Exception
     */
    public function testUpdateTopologyNotFound(): void
    {
        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/Topology/updateTopologyNotFoundRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::getTopologySchemaAction
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::getTopologySchema
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::getTopologyById
     *
     * @throws Exception
     */
    public function testGetTopologySchema(): void
    {
        $topology = $this->createTopologies()[0];

        $this->client->request(
            'GET',
            sprintf('/api/topologies/%s/schema.bpmn', $topology->getId()),
            server: [self::$AUTHORIZATION => $this->jwt],
        );
        $response = $this->client->getResponse();
        $response = (object) [
            'content' => $response->getContent(),
            'status'  => $response->getStatusCode(),
        ];

        self::assertEquals(200, $response->status);
        self::assertEquals($topology->getRawBpmn(), $response->content);
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::checkTopologySchemaDifferencesAction
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::getTopologyById
     * @covers \Hanaboso\PipesFramework\Utils\TopologySchemaUtils::getSchemaObject
     * @covers \Hanaboso\PipesFramework\Utils\TopologySchemaUtils::getSchemaFullIndexHash
     *
     * @return void
     * @throws Exception
     */
    public function testCheckTopologySchemaDifferences(): void
    {
        $topology = (new Topology())
            ->setName('Topology')
            ->setDescr('Topology')
            ->setEnabled(TRUE);
        $this->pfd($topology);

        $this->client->request(
            'POST',
            sprintf('/api/topologies/check/%s/schema.bpmn', $topology->getId()),
            [],
            [],
            [
                'ACCEPT'             => 'application/xml',
                'CONTENT_TYPE'       => 'application/xml',
                self::$AUTHORIZATION => $this->jwt,
            ],
            $this->getBpmn(),
        );

        $response = $this->client->getResponse();
        $response = $this->returnResponse($response);
        self::assertEquals(TRUE, $response->content->isDifferent);
        self::assertEquals(200, $response->status);
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::checkTopologySchemaDifferencesAction
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::getTopologyById
     * @covers \Hanaboso\PipesFramework\Utils\TopologySchemaUtils::getSchemaObject
     * @covers \Hanaboso\PipesFramework\Utils\TopologySchemaUtils::getSchemaFullIndexHash
     *
     * @throws Exception
     */
    public function testGetTopologySchemaNotFound(): void
    {
        $this->client->request('GET', '/api/topologies/999/schema.bpmn', server: [self::$AUTHORIZATION => $this->jwt]);
        $response = $this->client->getResponse();
        $response = $this->returnResponse($response);
        $content  = $response->content;

        self::assertEquals(500, $response->status);
        self::assertEquals(TopologyException::class, $content->type);
        self::assertEquals(2_402, $content->errorCode);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testCheckTopologySchemaNotFound(): void
    {
        $this->client->request(
            'POST',
            '/api/topologies/check/999/schema.bpmn',
            [],
            [],
            [
                'ACCEPT'             => 'application/xml',
                'CONTENT_TYPE'       => 'application/xml',
                self::$AUTHORIZATION => $this->jwt,
            ],
            $this->getBpmn(),
        );
        $response = $this->client->getResponse();
        $response = $this->returnResponse($response);
        $content  = $response->content;
        self::assertEquals(500, $response->status);
        self::assertEquals(TopologyException::class, $content->type);
        self::assertEquals(2_402, $content->errorCode);
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::saveTopologySchemaAction
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::saveTopologySchema
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::getTopologyById
     *
     * @throws Exception
     */
    public function testSaveTopologySchema(): void
    {
        $topology = (new Topology())
            ->setName('Topology')
            ->setDescr('Topology')
            ->setEnabled(TRUE);
        $this->pfd($topology);

        $this->client
            ->getContainer()
            ->set('hbpf.transport.curl_manager', self::createMock(CurlManagerInterface::class));

        $this->client->request(
            'PUT',
            sprintf('/api/topologies/%s/schema.bpmn', $topology->getId()),
            [],
            [],
            [
                'ACCEPT'             => 'application/xml',
                'CONTENT_TYPE'       => 'application/xml',
                self::$AUTHORIZATION => $this->jwt,
            ],
            $this->getBpmn(),
        );
        $response = $this->client->getResponse();
        $response = $this->returnResponse($response);
        self::assertEquals(200, $response->status);
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::saveTopologySchemaAction
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::saveTopologySchema
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::getTopologyById
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::getTopologyData
     *
     * @throws Exception
     */
    public function testSaveTopologySchemaNotFound(): void
    {
        $this->client->request(
            'PUT',
            '/api/topologies/999/schema.bpmn',
            [],
            [],
            [
                'ACCEPT'             => 'application/xml',
                'CONTENT_TYPE'       => 'application/xml',
                self::$AUTHORIZATION => $this->jwt,
            ],
            $this->getBpmn(),
        );
        $response = $this->client->getResponse();
        $response = $this->returnResponse($response);
        $content  = $response->content;
        self::assertEquals(500, $response->status);
        self::assertEquals(TopologyException::class, $content->type);
        self::assertEquals(2_402, $content->errorCode);
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::saveTopologySchemaAction
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::saveTopologySchema
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::getTopologyById
     *
     * @throws Exception
     */
    public function testSaveTopologySchemaNameNotFound(): void
    {
        $topology = (new Topology())
            ->setName('Topology')
            ->setDescr('Topology')
            ->setEnabled(TRUE);
        $this->pfd($topology);

        $this->client->request(
            'PUT',
            sprintf('/api/topologies/%s/schema.bpmn', $topology->getId()),
            [],
            [],
            [
                'ACCEPT'             => 'application/xml',
                'CONTENT_TYPE'       => 'application/xml',
                self::$AUTHORIZATION => $this->jwt,
            ],
            str_replace('name="Start Event"', '', $this->getBpmn()),
        );
        $response = $this->client->getResponse();
        $response = $this->returnResponse($response);
        self::assertEquals(400, $response->status);
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::saveTopologySchemaAction
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::saveTopologySchema
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::getTopologyById
     *
     * @throws Exception
     */
    public function testSaveTopologySchemaTypeNotExist(): void
    {
        $topology = (new Topology())
            ->setName('Topology')
            ->setDescr('Topology')
            ->setEnabled(TRUE);
        $this->pfd($topology);

        $this->client->request(
            'PUT',
            sprintf('/api/topologies/%s/schema.bpmn', $topology->getId()),
            [],
            [],
            [
                'ACCEPT'             => 'application/xml',
                'CONTENT_TYPE'       => 'application/xml',
                self::$AUTHORIZATION => $this->jwt,
            ],
            str_replace('pipes:pipesType="custom"', 'pipes:pipesType="Unknown"', $this->getBpmn()),
        );
        $response = $this->client->getResponse();
        $response = (object) [
            'content' => Json::decode((string) $response->getContent()),
            'status'  => $response->getStatusCode(),
        ];

        self::assertEquals(400, $response->status);
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::saveTopologySchemaAction
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::saveTopologySchema
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::getTopologyById
     *
     * @throws Exception
     */
    public function testSaveTopologySchemaCronNotValid(): void
    {
        $topology = (new Topology())
            ->setName('Topology')
            ->setDescr('Topology')
            ->setEnabled(TRUE);
        $this->pfd($topology);

        $this->client->request(
            'PUT',
            sprintf('/api/topologies/%s/schema.bpmn', $topology->getId()),
            [],
            [],
            [
                'ACCEPT'             => 'application/xml',
                'CONTENT_TYPE'       => 'application/xml',
                self::$AUTHORIZATION => $this->jwt,
            ],
            str_replace('pipes:cronTime="*/2 * * * *"', 'pipes:cronTime="Unknown"', $this->getBpmn()),
        );
        $response = $this->client->getResponse();
        $response = $this->returnResponse($response);
        self::assertEquals(400, $response->status);
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::saveTopologySchemaAction
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::saveTopologySchema
     *
     * @throws Exception
     */
    public function testSaveTopologySchema2(): void
    {
        $topology = (new Topology())
            ->setName('Topology')
            ->setDescr('Topology')
            ->setEnabled(TRUE);
        $this->pfd($topology);

        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/Topology/saveTopologyRequest.json',
            ['_id' => '5e395287c5317130b67a4e83'],
            [':id' => $topology->getId()],
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::getTopologySchemaAction
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::getTopologySchema
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::getTopologyById
     *
     * @throws Exception
     */
    public function testSaveAndGetTopologySchema(): void
    {
        $topology = (new Topology())
            ->setName('Topology')
            ->setDescr('Topology')
            ->setEnabled(TRUE)
            ->setRawBpmn($this->getBpmn());
        $this->pfd($topology);

        $this->client->request(
            'GET',
            sprintf('/api/topologies/%s/schema.bpmn', $topology->getId()),
            server: [self::$AUTHORIZATION => $this->jwt],
        );
        $response = $this->client->getResponse();
        $response = (object) [
            'content' => $response->getContent(),
            'status'  => $response->getStatusCode(),
        ];
        self::assertEquals(200, $response->status);
        self::assertEquals($this->getBpmn(), $response->content);
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::deleteTopologyAction
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::deleteTopology
     *
     * @throws Exception
     */
    public function testDeleteTopology(): void
    {
        $topology = (new Topology())
            ->setName('Topology')
            ->setDescr('Topology')
            ->setEnabled(TRUE);
        $this->pfd($topology);

        $this->mockHandler('deleteTopology', new ResponseDto(200, '', '{}', []));

        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/Topology/deleteTopologyRequest.json',
            [],
            [':id' => $topology->getId()],
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::deleteTopologyAction
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::deleteTopology
     *
     * @throws Exception
     */
    public function testDeleteTopologyErr(): void
    {
        $topology = (new Topology())
            ->setName('Topology')
            ->setDescr('Topology')
            ->setEnabled(TRUE);
        $this->pfd($topology);

        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/Topology/deleteTopologyErrRequest.json',
            ['message' => 'CurlManager::send() failed: cURL error 6: Could not resolve host: topology-api (see https://curl.haxx.se/libcurl/c/libcurl-errors.html)'],
            [':id' => $topology->getId()],
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::getCronTopologiesAction
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyHandler::getCronTopologies
     *
     * @throws Exception
     */
    public function testGetCronTopologies(): void
    {
        $this->mockHandler(
            'getCronTopologies',
            [
                'items' => [
                    [
                        'name'            => 'Topology-Node',
                        'node'            => 'Node',
                        'time'            => '*/1 * * * *',
                        'topology'        => 'Topology',
                        'topology_id'     => 'Topology ID',
                        'topology_status' => TRUE,
                    ],
                ],
            ],
        );

        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/Topology/getCronTopologiesRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::publishTopologyAction
     *
     * @throws Exception
     */
    public function testPublishTopologyAction(): void
    {
        $this->mockHandler('publishTopology', new ResponseDto(200, '', '{"success": true}', []));

        $topology = (new Topology())
            ->setName('Topology')
            ->setDescr('Topology')
            ->setEnabled(TRUE);
        $this->dm->persist($topology);
        $node = (new Node())->setTopology($topology->getId());
        $this->pfd($node);

        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/Topology/testPublishTopologyRequest.json',
            [],
            [':id' => $topology->getId()],
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::publishTopologyAction
     *
     * @throws Exception
     */
    public function testPublishTopologyErrAction(): void
    {
        $this->mockHandler('publishTopology', new TopologyException());

        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/Topology/testPublishTopologyErrRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::cloneTopologyAction
     *
     * @throws Exception
     */
    public function testCloneTopologyAction(): void
    {
        $topology = (new Topology())
            ->setName('Topology')
            ->setDescr('Topology')
            ->setEnabled(TRUE);
        $this->pfd($topology);

        $this->mockHandler(
            'cloneTopology',
            [
                'description' => 'desc',
                'name'        => 'name',
                'status'      => 'status',
                'type'        => 'type',
                '_id'         => '999',
            ],
        );

        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/Topology/cloneTopologyRequest.json',
            [],
            [':id' => $topology->getId()],
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::cloneTopologyAction
     *
     * @throws Exception
     */
    public function testCloneTopologyActionErr(): void
    {
        $this->mockHandler('cloneTopology', new NodeException());

        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/Topology/cloneTopologyErrRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::getCronTopologiesAction
     *
     * @throws Exception
     */
    public function testGetCronTopologiesErr(): void
    {
        $this->mockHandler('getCronTopologies', new CronException());
        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/Topology/getCronTopologiesErrRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::testAction
     *
     * @throws Exception
     */
    public function testAction(): void
    {
        $this->mockHandler('runTest', ['test' => 'success']);

        $topology = (new Topology())
            ->setName('Topology')
            ->setDescr('Topology')
            ->setEnabled(TRUE);
        $this->pfd($topology);

        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/Topology/testActionRequest.json',
            [],
            ['id' => $topology->getId()],
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyController::testAction
     *
     * @throws Exception
     */
    public function testActionErr(): void
    {
        $this->mockHandler('runTest', new LockException('Its lock.'));

        $topology = (new Topology())
            ->setName('Topology')
            ->setDescr('Topology')
            ->setEnabled(TRUE);
        $this->pfd($topology);

        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/Topology/testActionErrRequest.json',
            [],
            ['id' => $topology->getId()],
        );
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testGetTopologiesByIdAndNodeName(): void
    {
        $topology = (new Topology())
            ->setName('SameName')
            ->setDescr('Topology')
            ->setEnabled(TRUE)
            ->setBpmn($this->getBpmnArray())
            ->setRawBpmn($this->getBpmn());
        $this->pfd($topology);

        $node = (new Node())
            ->setName('testNode')
            ->setTopology($topology->getId());
        $this->pfd($node);

        $topology1 = (new Topology())
            ->setName('SameName')
            ->setDescr('Topology')
            ->setVersion(2)
            ->setEnabled(TRUE)
            ->setBpmn($this->getBpmnArray())
            ->setRawBpmn($this->getBpmn());
        $this->pfd($topology1);

        $node1 = (new Node())
            ->setName('testNode')
            ->setTopology($topology1->getId());
        $this->pfd($node1);

        $response = $this->sendRequest(
            'GET',
            sprintf('/topologies/%s/versions/node/%s', $topology->getId(), $node->getName()),
            [],
            [self::$AUTHORIZATION => $this->jwt],
        );

        self::assertEquals($topology->getId(), $response['body'][0]['id']);
        self::assertEquals($topology->getName(), $response['body'][0]['name']);
        self::assertEquals($topology->getVersion(), $response['body'][0]['version']);
        self::assertEquals($node->getId(), $response['body'][0]['nodes'][0]['id']);
        self::assertEquals($node->getName(), $response['body'][0]['nodes'][0]['name']);

        self::assertEquals($topology1->getId(), $response['body'][1]['id']);
        self::assertEquals($topology1->getName(), $response['body'][1]['name']);
        self::assertEquals($topology1->getVersion(), $response['body'][1]['version']);
        self::assertEquals($node1->getId(), $response['body'][1]['nodes'][0]['id']);
        self::assertEquals($node1->getName(), $response['body'][1]['nodes'][0]['name']);
    }

    /**
     * @return mixed[]
     */
    public static function topologyErrorProvider(): array
    {
        return [
            [new TopologyException(code: TopologyException::TOPOLOGY_NODE_TYPE_NOT_FOUND)],
            [new TopologyException(code: TopologyException::TOPOLOGY_NODE_NAME_NOT_FOUND)],
            [new TopologyException(code: TopologyException::TOPOLOGY_NODE_TYPE_NOT_EXIST)],
            [new LogicException()],
        ];
    }

    /**
     * @param int $count
     *
     * @return Topology[]
     * @throws Exception
     */
    private function createTopologies(int $count = 1): array
    {
        $topologies = [];
        for ($i = 1; $i <= $count; $i++) {
            $topology = (new Topology())
                ->setName(sprintf('name %s', $i))
                ->setDescr(sprintf('descr %s', $i))
                ->setEnabled(TRUE)
                ->setBpmn($this->getBpmnArray())
                ->setRawBpmn($this->getBpmn());
            $this->pfd($topology);

            $topologies[] = $topology;
        }

        return $topologies;
    }

    /**
     * @return string
     */
    private function getBpmn(): string
    {
        return File::getContent(sprintf('%s/data/schema.bpmn', __DIR__));
    }

    /**
     * @return mixed[]
     * @throws Exception
     */
    private function getBpmnArray(): array
    {
        return Json::decode(File::getContent(sprintf('%s/data/schema.json', __DIR__)));
    }

    /**
     * @param string $methodName
     * @param mixed  $res
     *
     * @throws Exception
     */
    private function mockHandler(string $methodName, mixed $res): void
    {
        $configuratorHandlerMock = $this->getMockBuilder(TopologyHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        if ($res instanceof Throwable) {
            $configuratorHandlerMock->method($methodName)->willThrowException($res);
        } else {
            $configuratorHandlerMock->method($methodName)->willReturn($res);
        }
        $container = $this->client->getContainer();
        $container->set('hbpf.configurator.handler.topology', $configuratorHandlerMock);
    }

}
