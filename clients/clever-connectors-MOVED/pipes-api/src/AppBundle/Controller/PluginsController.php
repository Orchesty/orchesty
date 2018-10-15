<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Controller;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Handler\PluginsHandler;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\CommonsBundle\Exception\PipesFrameworkException;
use Hanaboso\CommonsBundle\Traits\ControllerTrait;
use LogicException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Class PluginsController
 *
 * @package CleverConnectors\AppBundle\Controller
 */
class PluginsController extends FOSRestController
{

    use ControllerTrait;

    /**
     * @var PluginsHandler
     */
    private $handler;

    /**
     * PluginsController constructor.
     *
     * @param PluginsHandler $handler
     */
    public function __construct(PluginsHandler $handler)
    {
        $this->handler = $handler;
    }

    /**
     * @Route("/install", methods={"POST"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function installAction(Request $request): Response
    {
        try {
            return $this->getResponse($this->handler->install($request));
        } catch (Throwable $e) {
            return $this->processException($e);
        }
    }

    /**
     * @Route("/uninstall", methods={"GET"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function uninstallAction(Request $request): Response
    {
        try {
            return $this->getResponse($this->handler->uninstall($request));
        } catch (Throwable $e) {
            return $this->processException($e);
        }
    }

    /**
     * @Route("/check", methods={"POST"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function checkAction(Request $request): Response
    {
        try {
            return $this->getResponse($this->handler->check($request));
        } catch (Throwable $e) {
            return $this->processException($e);
        }
    }

    /**
     * @Route("/subscriber/create", methods={"POST"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function createSubscriberAction(Request $request): Response
    {
        try {
            $this->handler->createSubscriber($request);

            return $this->getResponse('');
        } catch (Throwable $e) {
            return $this->processException($e);
        }
    }

    /**
     * @Route("/subscriber/update", methods={"POST"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function updateSubscriberAction(Request $request): Response
    {
        try {
            $this->handler->updateSubscriber($request);

            return $this->getResponse('');
        } catch (Throwable $e) {
            return $this->processException($e);
        }
    }

    /**
     * @Route("/subscriber/delete", methods={"POST"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function deleteSubscriberAction(Request $request): Response
    {
        try {
            $this->handler->deleteSubscriber($request);

            return $this->getResponse('');
        } catch (Throwable $e) {
            return $this->processException($e);
        }
    }

    /**
     * @Route("/get-distribution-list", methods={"GET"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function getDistributionListsAction(Request $request): Response
    {
        try {
            return $this->getResponse($this->handler->getDistributionLists($request));
        } catch (Throwable $e) {
            return $this->processException($e);
        }
    }

    /**
     * @Route("/create-distribution-list", methods={"POST"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function createDistributionListAction(Request $request): Response
    {
        try {
            return $this->getResponse($this->handler->createDistributionList($request));
        } catch (Throwable $e) {
            return $this->processException($e);
        }
    }

    /**
     * @Route("/subscriber/validate", methods={"POST"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function validateSubscriberAction(Request $request): Response
    {
        try {
            $this->handler->validateSubscriber($request);

            return $this->getResponse('');
        } catch (Throwable $e) {
            return $this->processException($e);
        }
    }

    /**
     * @param Throwable $e
     *
     * @return Response
     */
    private function processException(Throwable $e): Response
    {
        $code      = 500;
        $className = get_class($e);

        if ($className === SystemException::class) {
            $sysNotFound = [
                SystemException::SYSTEM_NOT_FOUND,
                SystemException::SYSTEM_OR_USER_NOT_FOUND,
                SystemException::SYSTEM_PROPERTY_NOT_FOUND,
                SystemException::MISMATCH_URL,
            ];
            if (in_array($e->getCode(), $sysNotFound)) {
                $code = 404;
            }
        } else if ($className === LogicException::class) {
            $code = 404;
        } else if ($className === CleverConnectorsException::class || $className === PipesFrameworkException::class) {
            $code = 400;
        }

        return $this->getErrorResponse($e, $code);
    }

}
