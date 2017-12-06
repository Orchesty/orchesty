<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFMailerBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\PipesFramework\Commons\Traits\ControllerTrait;
use Hanaboso\PipesFramework\HbPFMailerBundle\Handler\MailHandler;
use Hanaboso\PipesFramework\Mailer\Exception\MailerException;
use Hanaboso\PipesFramework\Mailer\MessageBuilder\MessageBuilderException;
use Hanaboso\PipesFramework\Mailer\Transport\TransportException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ApiController
 *
 * @package Hanaboso\PipesFramework\HbPFMailerBundle\Controller
 *
 * @Route(service="hbpf.mailer.controller.api")
 */
class ApiController extends FOSRestController
{

    use ControllerTrait;

    /**
     * @var MailHandler
     */
    private $mailHandler;

    /**
     * ApiController constructor.
     *
     * @param MailHandler $mailHandler
     */
    public function __construct(MailHandler $mailHandler)
    {
        $this->mailHandler = $mailHandler;
    }

    /**
     * @Route("/mailer/{handlerId}/send", defaults={}, requirements={"_format"="json|xml"})
     * @Method({"POST", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $handlerId
     *
     * @return Response
     */
    public function sendAction(Request $request, string $handlerId): Response
    {
        try {
            $this->mailHandler->send($handlerId, $request->request->all());

            return $this->getResponse(['status' => 'OK']);
        } catch (ServiceNotFoundException | MessageBuilderException | TransportException | MailerException $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @Route("/mailer/{handlerId}/send/test", defaults={}, requirements={"_format"="json|xml"})
     *
     * @param Request $request
     * @param string  $handlerId
     *
     * @return Response
     */
    public function sendTestAction(Request $request, string $handlerId): Response
    {
        try {
            $this->mailHandler->testSend($handlerId, $request->request->all());

            return $this->getResponse(['status' => 'OK']);
        } catch (ServiceNotFoundException | MessageBuilderException | TransportException | MailerException $e) {
            return $this->getErrorResponse($e);
        }
    }

}
