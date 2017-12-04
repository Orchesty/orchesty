<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 21.9.17
 * Time: 8:32
 */

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\PipesFramework\Commons\Traits\ControllerTrait;
use Hanaboso\PipesFramework\Configurator\TopologyControlling\Messages\TopologyMessage;
use Hanaboso\PipesFramework\Configurator\TopologyControlling\TopologyControllingProducer;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\GeneratorHandler;
use Hanaboso\PipesFramework\TopologyGenerator\Exception\TopologyGeneratorException;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class GeneratorController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 *
 * @Route(service="hbpf.configurator.generator_controller")
 */
class GeneratorController extends FOSRestController
{

    use ControllerTrait;

    /**
     * @var LoggerInterface|NULL
     */
    protected $logger = NULL;

    /**
     * @var GeneratorHandler|NULL
     */
    protected $generatorHandler = NULL;

    /**
     * @var TopologyControllingProducer|NULL
     */
    protected $topologyControllingProducer = NULL;

    /**
     * @Route("/topology/generate/{id}")
     * @Method({"GET"})
     *
     * @param string $id
     *
     * @return Response
     * @throws TopologyGeneratorException
     */
    public function generateAction(string $id): Response
    {
        //TODO: Make much better !!!!
        $this->construct();
        $statusCode = 400;

        if ($this->generatorHandler) {
            $result = $this->generatorHandler->generateTopology($id);
            //@todo better solution
            if (is_array($result)) {
                $statusCode = 200;
            }
        }

        return $this->getResponse(["result" => $statusCode], $statusCode);
    }

    /**
     * @Route("/topology/run/{id}")
     * @Method({"GET"})
     *
     * @param string $id
     *
     * @return Response
     * @throws TopologyGeneratorException
     */
    public function runAction(string $id): Response
    {
        //TODO: Make much better !!!!
        $this->construct();
        $statusCode = 400;
        $result     = NULL;

        if ($this->generatorHandler) {
            $result = $this->generatorHandler->runTopology($id);
            //@todo better solution
            if (is_array($result)) {
                $statusCode = 200;
            }
        }

        return $this->getResponse(["result" => $statusCode], $statusCode);
    }

    /**
     * @Route("/topology/stop/{id}")
     * @Method({"GET"})
     *
     * @param string $id
     *
     * @return Response
     * @throws TopologyGeneratorException
     */
    public function stopAction(string $id): Response
    {
        //TODO: Make much better !!!!
        $this->construct();
        $statusCode = 200;

        if ($this->topologyControllingProducer) {
            $message = new TopologyMessage($id, TopologyMessage::STOP);
            $this->topologyControllingProducer->publish($message->getMessage());
        }

        /*if ($this->generatorHandler) {
            $result = $this->generatorHandler->stopTopology($id);
            //@todo better solution
            if (is_array($result)) {
                $statusCode = 200;
            }
        }*/

        return $this->getResponse(["result" => $statusCode], $statusCode);
    }

    /**
     * @Route("/topology/delete/{id}")
     * @Method({"GET"})
     *
     * @param string $id
     *
     * @return Response
     * @throws TopologyGeneratorException
     * @throws TopologyGeneratorException
     */
    public function deleteAction(string $id): Response
    {
        //TODO: Make much better !!!!
        $this->construct();
        $statusCode = 200;

        if ($this->topologyControllingProducer) {

            $message = new TopologyMessage($id, TopologyMessage::DELETE);
            $this->topologyControllingProducer->publish($message->getMessage());
        }

        /*if ($this->generatorHandler) {
            $this->generatorHandler->stopTopology($id);
            $this->generatorHandler->destroyTopology($id);
            $statusCode = 200;
        }*/

        return $this->getResponse(["result" => $statusCode], $statusCode);
    }

    /**
     * @Route("/topology/info/{id}")
     * @Method("{GET}")
     *
     * @param string $id
     *
     * @return Response
     * @throws TopologyGeneratorException
     */
    public function infoAction(string $id): Response
    {
        //TODO: Make much better !!!!
        $this->construct();
        $statusCode = 400;
        $result     = NULL;

        if ($this->generatorHandler) {
            $result     = $this->generatorHandler->infoTopology($id);
            $statusCode = 200;
        }

        return $this->getResponse(["result" => $statusCode, "docker_info" => $result], $statusCode);
    }

    /**
     * fake __construct
     */
    public function construct(): void
    {
        if (!$this->generatorHandler) {
            $this->generatorHandler = $this->container->get('hbpf.handler.generator_handler');
        }
        if (!$this->logger) {
            $this->logger = $this->container->get('monolog.logger.security');
        }

        if (!$this->topologyControllingProducer) {
            $this->topologyControllingProducer = $this->container->get('rabbit-mq.producer.topology-destroy');
        }
    }

}
