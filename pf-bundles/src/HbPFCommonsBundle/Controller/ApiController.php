<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFCommonsBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\PipesFramework\Commons\Message\MessageInterface;
use Hanaboso\PipesFramework\HbPFCommonsBundle\Handler\NodeHandler;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ApiController
 *
 * @package Hanaboso\PipesFramework\HbPFCommonsBundle\Controller
 */
class ApiController extends FOSRestController
{

    /**
     * @var NodeHandler
     */
    private $nodeHandler;

    /**
     * ApiController constructor.
     *
     * @param NodeHandler $nodeHandler
     */
    public function __construct(NodeHandler $nodeHandler)
    {
        $this->nodeHandler = $nodeHandler;
    }

    /**
     * @Route("/api/nodes/{nodeId}/process", defaults={}, requirements={"nodeId": "\w+"})
     * @ParamConverter(
     *     "message",
     *     class="Hanaboso\PipesFramework\Commons\Message\DefaultMessage",
     *     converter="fos_rest.request_body"
     * )
     *
     * @param string           $nodeId
     * @param MessageInterface $message
     *
     * @return Response
     */
    public function nodeAction(string $nodeId, MessageInterface $message): Response
    {
        $data = $this->nodeHandler->processData($nodeId, $message);

        return $this->handleView($this->view($data));
    }

}
