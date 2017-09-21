<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 21.9.17
 * Time: 8:32
 */

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\PipesFramework\HbPFApiGatewayBundle\Handler\GeneratorHandler;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class GeneratorController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 *
 * * @Route(service="hbpf.api_gateway.controller.generator")
 */
class GeneratorController extends FOSRestController
{

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var GeneratorHandler
     */
    private $generatorHandler;

    /**
     * GeneratorController constructor.
     *
     * @param GeneratorHandler $generatorHandler
     * @param LoggerInterface  $logger
     */
    public function __construct(
        GeneratorHandler $generatorHandler,
        LoggerInterface $logger
    )
    {
        $this->generatorHandler = $generatorHandler;
        $this->logger           = $logger;
    }

    /**
     * @Route("/topology/generate/topology/{id}")
     * @Method({ "GET"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function runAction(string $id): Response
    {
        $result     = $this->generatorHandler->generateTopology($id);
        $statusCode = $result ? 200 : 400;

        return $this->handleView($this->view([], $statusCode, []));
    }

}
