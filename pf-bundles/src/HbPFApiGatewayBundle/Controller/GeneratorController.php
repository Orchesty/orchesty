<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 6.10.17
 * Time: 14:50
 */

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class GeneratorController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 *
 * @Route(service="hbpf.api_gateway.controller.generator")
 */
class GeneratorController extends FOSRestController
{

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
        return $this->forward(
            'HbPFConfiguratorBundle:Generator:generate',
            ['id' => $id]
        );
    }

    /**
     * @Route("/topology/run/{id}")
     * @Method({"GET"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function runAction(string $id): Response
    {
        return $this->forward(
            'HbPFConfiguratorBundle:Generator:run',
            ['id' => $id]
        );
    }

    /**
     * @Route("/topology/stop/{id}")
     * @Method({"GET"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function stopAction(string $id): Response
    {
        return $this->forward(
            'HbPFConfiguratorBundle:Generator:stop',
            ['id' => $id]
        );
    }

    /**
     * @Route("/topology/delete/{id}")
     * @Method({"GET"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function deleteAction(string $id): Response
    {
        return $this->forward(
            'HbPFConfiguratorBundle:Generator:delete',
            ['id' => $id]
        );
    }

    /**
     * @Route("/topology/info/{id}")
     * @Method({"GET"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function infoAction(string $id): Response
    {
        return $this->forward(
            'HbPFConfiguratorBundle:Generator:info',
            ['id' => $id]
        );
    }

}
