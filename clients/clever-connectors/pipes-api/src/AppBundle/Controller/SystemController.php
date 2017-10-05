<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Controller;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Handler\SystemHandler;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\PipesFramework\Utils\ControllerUtils;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class SystemController
 *
 * @package CleverConnectors\AppBundle\Controller
 *
 * @Route(service="systems.controller")
 */
class SystemController extends FOSRestController
{

    /**
     * @var SystemHandler
     */
    private $handler;

    /**
     * SystemController constructor.
     *
     * @param SystemHandler $handler
     */
    public function __construct(SystemHandler $handler)
    {
        $this->handler = $handler;
    }

    /**
     * @Route("/systems/{systemKey}", requirements={"system": "[\w|\.]+"})
     * @Method({"GET", "OPTIONS"})
     *
     * @param string $systemKey
     *
     * @return Response
     */
    public function getSystemAction(string $systemKey): Response
    {
        try {
            return new JsonResponse($this->handler->getSystem($systemKey), 200);
        } catch (SystemException $e) {
            return new JsonResponse(ControllerUtils::createExceptionData($e), 500);
        }
    }

    /**
     * @Route("/systems")
     * @Method({"GET", "OPTIONS"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function getSystemsAction(Request $request): Response
    {
        try {
            return new JsonResponse($this->handler->getSystems(
                $request->query->get('user', NULL),
                $request->query->get('group', NULL)
            ), 200);
        } catch (SystemException $e) {
            return new JsonResponse(ControllerUtils::createExceptionData($e), 500);
        }
    }

    /**
     * @Route("/user_systems/user/{userId}", requirements={"userId": "\w+"})
     * @Method({"GET", "OPTIONS"})
     *
     * @param string $userId
     *
     * @return Response
     */
    public function getUserSystemsAction(string $userId): Response
    {
        try {
            return new JsonResponse($this->handler->getUserSystems($userId), 200);
        } catch (SystemException $e) {
            return new JsonResponse(ControllerUtils::createExceptionData($e), 500);
        }
    }

    /**
     * @Route("/user_systems/user/{userId}/system/{systemKey}/install", requirements={"userId": "\w+", "systemKey": "[\w|\.]+"})
     * @Method({"POST", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $userId
     * @param string  $systemKey
     *
     * @return Response
     */
    public function installSystemAction(Request $request, string $userId, string $systemKey): Response
    {
        try {
            return new JsonResponse($this->handler->installSystem($userId, $systemKey, $request->request->all()), 200);
        } catch (SystemException $e) {
            return new JsonResponse(ControllerUtils::createExceptionData($e), 500);
        }
    }

    /**
     * @Route("/user_systems/user/{userId}/system/{systemKey}/settings", requirements={"userId": "\w+", "systemKey": "[\w|\.]+"})
     * @Method({"POST", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $userId
     * @param string  $systemKey
     *
     * @return Response
     */
    public function saveSystemSettingsAction(Request $request, string $userId, string $systemKey): Response
    {
        try {
            return new JsonResponse(
                $this->handler->saveSystemSettings($userId, $systemKey, $request->request->all()),
                200
            );
        } catch (SystemException $e) {
            return new JsonResponse(ControllerUtils::createExceptionData($e), 500);
        }
    }

    /**
     * @Route("/user_systems/user/{userId}/system/{systemKey}/uninstall")
     * @Method({"GET", "OPTIONS"})
     *
     * @param string $userId
     * @param string $systemKey
     *
     * @return Response
     */
    public function uninstallSystemAction(string $userId, string $systemKey): Response
    {
        try {
            return new JsonResponse($this->handler->uninstallSystem($userId, $systemKey), 200);
        } catch (SystemException $e) {
            return new JsonResponse(ControllerUtils::createExceptionData($e), 500);
        }
    }

    /**
     * @Route("/user_systems/user/{userId}/system/{systemKey}/switch_token", requirements={"userId": "\w+", "systemKey": "[\w|\.]+"})
     * @Method({"PUT", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $userId
     * @param string  $systemKey
     *
     * @return Response
     */
    public function switchSystemTokenAction(Request $request, string $userId, string $systemKey): Response
    {
        try {
            return new JsonResponse($this->handler->switchToken($userId, $systemKey, $request->request->all()), 200);
        } catch (SystemException $e) {
            return new JsonResponse(ControllerUtils::createExceptionData($e), 500);
        }
    }

    /**
     * @Route("/user_systems/user/{userId}/system/{systemKey}/sync", requirements={"userId": "\w+", "systemKey": "[\w|\.]+"})
     * @Method({"GET", "OPTIONS"})
     *
     * @param string $userId
     * @param string $systemKey
     *
     * @return Response
     */
    public function synchronizeSubscriptionsAction(string $userId, string $systemKey): Response
    {
        try {
            // TODO: Implement synchronizeSubscriptions
            return new JsonResponse([], 202);
        } catch (SystemException $e) {
            return new JsonResponse(ControllerUtils::createExceptionData($e), 500);
        }
    }

    /**
     * @Route("/user_systems/user/{userId}/system/{systemKey}/set_password", requirements={"userId": "\w+", "systemKey": "[\w|\.]+"})
     * @Method({"PUT", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $userId
     * @param string  $systemKey
     *
     * @return Response
     */
    public function setPasswordAction(Request $request, string $userId, string $systemKey): Response
    {
        try {
            return new JsonResponse($this->handler->setPassword($userId, $systemKey, $request->request->all()), 200);
        } catch (SystemException $e) {
            return new JsonResponse(ControllerUtils::createExceptionData($e), 500);
        }
    }

    /**
     * @Route("/user_systems/user/{userId}/system/{systemKey}/authorize_redirect/{redirectUrl}",
     *     requirements={"userId": "\w+", "systemKey": "[\w|\.]+", "redirectUrl": "\w+"})
     * @Method({"GET", "OPTIONS"})
     *
     * @param string $userId
     * @param string $systemKey
     * @param string $redirectUrl
     *
     * @return Response|null
     */
    public function authorizeSystemAction(string $userId, string $systemKey, string $redirectUrl): ?Response
    {
        try {
            $this->handler->authorize($userId, $systemKey, $redirectUrl);

            return NULL;
        } catch (SystemException $e) {
            return new JsonResponse(ControllerUtils::createExceptionData($e), 500);
        }
    }

    /**
     * @Route("/user_systems/user/{userId}/system/{systemKey}/saveToken", requirements={"userId": "\w+", "systemKey": "[\w|\.]+"})
     * @Method({"POST", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $userId
     * @param string  $systemKey
     *
     * @return Response
     */
    public function userSaveTokenAction(Request $request, string $userId, string $systemKey): Response
    {
        $url = $this->handler->saveToken($userId, $systemKey, $request->request->all());

        return new RedirectResponse($url);
    }

    /**
     * @Route("/user_systems/saveToken")
     * @Method({"POST", "OPTIONS"})
     *
     * @param Request $request
     *
     * @return Response
     * @throws CleverConnectorsException
     */
    public function saveTokenAction(Request $request): Response
    {
        if (!$request->query->has('state')) {
            throw new CleverConnectorsException(
                'Missing [userId:SystemKey] in request query.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        $str = $request->query->get('state');
        $str = base64_decode($str);
        $str = explode(':', $str);
        $url = $this->handler->saveToken($str[0], $str[1], $request->request->all());

        return new RedirectResponse($url);
    }

}