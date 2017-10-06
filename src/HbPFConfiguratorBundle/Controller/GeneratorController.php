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

    /**
     * @var LoggerInterface|NULL
     */
    protected $logger = NULL;

    /**
     * @var GeneratorHandler|NULL
     */
    private $generatorHandler = NULL;

    /**
     * GeneratorController constructor.
     *
     * @param GeneratorHandler $generatorHandler
     * @param LoggerInterface  $logger
     */
    /*public function __construct(
        GeneratorHandler $generatorHandler,
        LoggerInterface $logger
    )
    {
        $this->generatorHandler = $generatorHandler;
        $this->logger           = $logger;
    }*/

    /**
     * @Route("/topology/generate/{id}")
     * @Method({"GET"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function generateAction(string $id): Response
    {
        //TODO: Make much better !!!!
        $this->construct();

        $result     = $this->generatorHandler->generateTopology($id);
        $statusCode = $result ? 200 : 400;

        return $this->handleView($this->view([], $statusCode, []));
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
