<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Controller;

use Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Handler\NotificationHandler;
use Hanaboso\UserBundle\Document\User;
use Hanaboso\Utils\Traits\ControllerTrait;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

/**
 * Class NotificationController
 *
 * @package Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Controller
 */
final class NotificationController extends AbstractController
{

    use ControllerTrait;

    /**
     * NotificationController constructor.
     *
     * @param NotificationHandler $handler
     */
    public function __construct(private NotificationHandler $handler)
    {
        $this->logger = new NullLogger();
    }

    /**
     * @return Response
     */
    #[Route('/notifications/subscriptions', methods: ['GET'])]
    public function listSubscriptionsAction(): Response
    {
        try {
            /** @var User $user */
            $user = $this->getUser();

            return $this->getResponse($this->handler->listSubscriptions($user->getId()));
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/notifications/subscriptions', methods: ['PUT'])]
    public function upsertSubscriptionAction(Request $request): Response
    {
        try {
            /** @var User $user */
            $user = $this->getUser();

            return $this->getResponse($this->handler->upsertSubscription($user->getId(), $request->getContent()));
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

}
