<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFMailerBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\PipesFramework\HbPFMailerBundle\Handler\MailHandler;
use Hanaboso\PipesFramework\Mailer\Exception\MailerException;
use Hanaboso\PipesFramework\Mailer\MessageBuilder\MessageBuilderException;
use Hanaboso\PipesFramework\Mailer\Transport\TransportException;
use Hanaboso\PipesFramework\Utils\ControllerUtils;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ApiController
 *
 * @package Hanaboso\PipesFramework\HbPFMailerBundle\Controller
 *
 * @Route(service="hbpf.mailer.controller.api")
 */
class ApiController extends FOSRestController
{

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
     * @Route("/api/mailer/{handlerId}/send", defaults={}, requirements={"_format"="json|xml"})
     * @Method({"POST", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $handlerId
     *
     * @return JsonResponse
     */
    public function sendAction(Request $request, string $handlerId): JsonResponse
    {
        try {
            $this->mailHandler->send($handlerId, $request->request->all());

            return new JsonResponse(['status' => 'OK']);
        } catch (ServiceNotFoundException | MessageBuilderException | TransportException | MailerException $e) {
            return new JsonResponse(ControllerUtils::createExceptionData($e), 500);
        }
    }

    /**
     * @Route("/api/mailer/{handlerId}/send/test", defaults={}, requirements={"_format"="json|xml"})
     *
     * @param Request $request
     * @param string  $handlerId
     *
     * @return JsonResponse
     */
    public function sendTestAction(Request $request, string $handlerId): JsonResponse
    {
        try {
            $this->mailHandler->testSend($handlerId, $request->request->all());

            return new JsonResponse(['status' => 'OK']);
        } catch (ServiceNotFoundException | MessageBuilderException | TransportException | MailerException $e) {
            return new JsonResponse(ControllerUtils::createExceptionData($e), 500);
        }
    }

}
