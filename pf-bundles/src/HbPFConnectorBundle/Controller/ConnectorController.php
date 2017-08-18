<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/18/17
 * Time: 2:00 PM
 */

namespace Hanaboso\PipesFramework\HbPFConnectorBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\PipesFramework\HbPFConnectorBundle\Handler\ConnectorHandler;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ConnectorController
 *
 * @package Hanaboso\PipesFramework\HbPFConnectorBundle\Controller
 */
class ConnectorController extends FOSRestController
{

    /**
     * @var ConnectorHandler
     */
    private $handler;

    /**
     * ConnectorController constructor.
     *
     * @param ConnectorHandler $handler
     */
    function __construct(ConnectorHandler $handler)
    {
        $this->handler = $handler;
    }

    /**
     * @Route("/api/conector/{id}/topology/{token}", defaults={}, requirements={"id": "\w+", "token": "\w+"})
     * @Method('GET')
     *
     * @param string  $id
     * @param string  $token
     * @param Request $request
     *
     * @return Response
     */
    public function processEvent(string $id, string $token, Request $request): Response
    {
        $data = $this->handler->processEvent($id, $token, $request->request->all());

        return $this->handleView($this->view($data));
    }

}