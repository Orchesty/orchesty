<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Controller;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Handler\SystemHandler;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use Exception;
use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\PipesFramework\Commons\Exception\PipesFrameworkException;
use Hanaboso\PipesFramework\Commons\Utils\Base64;
use Hanaboso\PipesFramework\Utils\ControllerUtils;
use InvalidArgumentException;
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
 * @Route(service="cc.systems.controller")
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
     * @Route("/systems/{systemKey}")
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
            return self::processException($e);
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
            return self::processException($e);
        }
    }

    /**
     * @Route("/user_systems/user/{userId}")
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
            return self::processException($e);
        }
    }

    /**
     * @Route("/user_systems/user/{userId}/system/{systemKey}")
     * @Method({"GET", "OPTIONS"})
     *
     * @param string $userId
     * @param string $systemKey
     *
     * @return Response
     */
    public function getUserSystemAction(string $userId, string $systemKey): Response
    {
        try {
            return new JsonResponse($this->handler->getUserSystem($userId, $systemKey), 200);
        } catch (SystemException $e) {
            return self::processException($e);
        }
    }

    /**
     * @Route("/user_systems/user/{userId}/system/{systemKey}/install")
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
        } catch (SystemException | CleverConnectorsException | PipesFrameworkException $e) {
            return self::processException($e);
        }
    }

    /**
     * @Route("/user_systems/user/{userId}/system/{systemKey}/settings")
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
            return self::processException($e);
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
            return self::processException($e);
        }
    }

    /**
     * @Route("/user_systems/user/{userId}/system/{systemKey}/switch_token")
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
        } catch (SystemException | PipesFrameworkException $e) {
            return self::processException($e);
        }
    }

    /**
     * @Route("/user_systems/user/{userId}/system/{systemKey}/sync")
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
            $this->handler->synchronizeSubscriptions($userId, $systemKey);

            return new JsonResponse([], 202);
        } catch (SystemException $e) {
            return self::processException($e);
        }
    }

    /**
     * @Route("/user_systems/user/{userId}/system/{systemKey}/set_password")
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
        } catch (SystemException | PipesFrameworkException $e) {
            return self::processException($e);
        }
    }

    /**
     * @Route("/user_systems/user/{userId}/system/{systemKey}/authorize")
     * @Method({"GET", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $userId
     * @param string  $systemKey
     *
     * @return null|Response
     */
    public function authorizeSystemAction(Request $request, string $userId, string $systemKey): ?Response
    {
        try {

            $redirectUrl = $request->query->get('redirect_url', NULL);
            if (!$redirectUrl) {
                throw new InvalidArgumentException('Missing "redirect_url" query parameter.');
            }

            $this->handler->authorize($userId, $systemKey, $redirectUrl);

            return new RedirectResponse($redirectUrl);
        } catch (SystemException $e) {
            return self::processException($e);
        }
    }

    /**
     * @Route("/user_systems/user/{userId}/system/{systemKey}/saveToken")
     * @Method({"GET", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $userId
     * @param string  $systemKey
     *
     * @return Response
     */
    public function userSaveTokenAction(Request $request, string $userId, string $systemKey): Response
    {
        $url = $this->handler->saveToken($userId, $systemKey, $request->query->all());

        return new RedirectResponse($url);
    }

    /**
     * @Route("/user_systems/saveToken")
     * @Method({"GET", "OPTIONS"})
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

        $str = explode(':', Base64::base64UrlDecode($request->query->get('state')));
        $url = $this->handler->saveToken($str[0], $str[1], $request->query->all());

        return new RedirectResponse($url);
    }

    /**
     * @param Exception $e
     *
     * @return JsonResponse
     */
    private static function processException(Exception $e): JsonResponse
    {
        $code = 500;

        $className = get_class($e);
        if ($className == SystemException::class) {
            $sysNotFound = [
                SystemException::SYSTEM_NOT_FOUND,
                SystemException::SYSTEM_OR_USER_NOT_FOUND,
                SystemException::SYSTEM_PROPERTY_NOT_FOUND,
            ];
            if (in_array($e->getCode(), $sysNotFound)) {
                $code = 404;
            }
        }

        if ($className == CleverConnectorsException::class) {
            if ($e->getCode() == CleverConnectorsException::SYSTEM_ALREADY_INSTALLED) {
                $code = 400;
            }
        }

        if ($className == PipesFrameworkException::class) {
            if ($e->getCode() == PipesFrameworkException::REQUIRED_PARAMETER_NOT_FOUND) {
                $code = 400;
            }
        }

        return new JsonResponse(ControllerUtils::createExceptionData($e), $code);
    }

}