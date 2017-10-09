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
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\GeneratorHandler;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class GeneratorController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 *
 * @Route(service="hbpf.configurator.generator_controller")
 */
class GeneratorController extends FOSRestController
{

    /**
     * @var LoggerInterface|NULL
     */
    protected $logger = NULL;

    /**
     * @var GeneratorHandler|NULL
     */
    private $generatorHandler = NULL;

    /**
     * @Route("/topology/generate/{id}")
     * @Method({"GET"})
     *
     * @param string $id
     *
     * @return JsonResponse
     */
    public function generateAction(string $id): JsonResponse
    {
        //TODO: Make much better !!!!
        $this->construct();
        $statusCode = 400;

        if ($this->generatorHandler) {
            $result = $this->generatorHandler->generateTopology($id);
            if ($result) {
                $statusCode = 200;
            }
        }

        return new JsonResponse(["result" => $statusCode], $statusCode, []);
    }

    /**
     * @Route("/topology/run/{id}")
     * @Method({"GET"})
     *
     * @param string $id
     *
     * @return JsonResponse
     */
    public function runAction(string $id): JsonResponse
    {
        //TODO: Make much better !!!!
        $this->construct();
        $statusCode = 400;

        if ($this->generatorHandler) {
            $result = $this->generatorHandler->runTopology($id);
            if ($result) {
                $statusCode = 200;
            }
        }

        return new JsonResponse(["result" => $statusCode], $statusCode, []);
    }

    /**
     *
     */
    public function construct(): void
    {
        if (!$this->generatorHandler) {
            $this->generatorHandler = $this->container->get('hbpf.handler.generator_handler');
        }
        if (!$this->logger) {
            $this->logger = $this->container->get('monolog.logger.security');
        }
    }

}
