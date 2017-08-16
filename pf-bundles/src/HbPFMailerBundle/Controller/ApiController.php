<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFMailerBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\PipesFramework\Mailer\Mailer;
use Hanaboso\PipesFramework\Mailer\MailerException;
use Hanaboso\PipesFramework\Mailer\MessageHandler\MessageHandlerException;
use Hanaboso\PipesFramework\Mailer\MessageHandler\MessageHandlerInterface;
use Hanaboso\PipesFramework\Mailer\Transport\TransportException;
use Hanaboso\PipesFramework\Utils\ControllerUtils;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ApiController
 *
 * @package Hanaboso\PipesFramework\HbPFMailerBundle\Controller
 */
class ApiController extends FOSRestController
{

    /**
     * @Route("/api/mailer/{handlerId}/send", defaults={}, requirements={"_format"="json|xml"})
     *
     * @param Request $request
     * @param string  $handlerId
     *
     * @return Response
     */
    public function sendAction(Request $request, string $handlerId): Response
    {
        $response = new JsonResponse();

        try {
            /** @var MessageHandlerInterface $messageHandler */
            $messageHandler = $this->get('hbpf.mailer.handler.' . $handlerId);

            /** @var Mailer $mailer */
            $mailer = $this->get('hbpf.mailer.service');

            $mailer->renderAndSend($messageHandler->buildTransportMessage($request->request->all()));

            $data = [
                'status' => 'OK',
            ];
        } catch (ServiceNotFoundException $e) {
            $data = [
                'status'     => 'ERROR',
                'error_code' => 0,
                'type'       => get_class($e),
                'message'    => sprintf('Mailer[id=%s] was not found', $handlerId),
            ];

            $response->setStatusCode(500);
        } catch (MessageHandlerException|TransportException $e) {
            $data = ControllerUtils::createExceptionData($e);

            $response->setStatusCode(500);
        }

        $response->setData($data);

        return $response;
    }

    /**
     * @Route("/api/mailer/{handlerId}/send/test", defaults={}, requirements={"_format"="json|xml"})
     *
     * @param Request $request
     * @param string  $handlerId
     *
     * @return Response
     */
    public function sendTestAction(Request $request, string $handlerId): Response
    {
        $response = new JsonResponse();

        try {
            /** @var MessageHandlerInterface $messageHandler */
            $messageHandler = $this->get('hbpf.mailer.handler.' . $handlerId);

            /** @var Mailer $mailer */
            $mailer = $this->get('hbpf.mailer.service');

            $mailer->renderAndSendTest($messageHandler->buildTransportMessage($request->request->all()));

            $data = [
                'status' => 'OK',
            ];
        } catch (ServiceNotFoundException $e) {
            $data = [
                'status'     => 'ERROR',
                'error_code' => 0,
                'type'       => get_class($e),
                'message'    => sprintf('Mailer[id=%s] was not found', $handlerId),
            ];

            $response->setStatusCode(500);
        } catch (MessageHandlerException|TransportException|MailerException $e) {
            $data = ControllerUtils::createExceptionData($e);

            $response->setStatusCode(500);
        }

        $response->setData($data);

        return $response;
    }

}
