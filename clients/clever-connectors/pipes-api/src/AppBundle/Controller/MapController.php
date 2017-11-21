<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Controller;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Handler\MapHandler;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\PipesFramework\Commons\Exception\PipesFrameworkException;
use LogicException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Class MapController
 *
 * @package CleverConnectors\AppBundle\Controller
 */
class MapController extends FOSRestController
{

    /**
     * @var MapHandler
     */
    private $handler;

    /**
     * SystemController constructor.
     *
     * @param MapHandler $handler
     */
    public function __construct(MapHandler $handler)
    {
        $this->handler = $handler;
    }

    /**
     * @Route("map/user/{userId}/system/{systemKey}")
     * @Method({"POST", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $userId
     * @param string  $systemKey
     *
     * @return Response
     */
    public function createAction(Request $request, string $userId, string $systemKey): Response
    {
        try {
            return new JsonResponse($this->handler->create($userId, $systemKey, $request->request->all()), 200);
        } catch (Throwable $e) {
            return self::processException($e);
        }
    }

    /**
     * @Route("map/{id}/user/{userId}/system/{systemKey}")
     * @Method({"PUT", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $userId
     * @param string  $systemKey
     *
     * @return Response
     */
    public function updateAction(Request $request, string $id, string $userId, string $systemKey): Response
    {
        try {
            return new JsonResponse($this->handler->update($id, $userId, $systemKey, $request->request->all()), 200);
        } catch (Throwable $e) {
            return self::processException($e);
        }
    }

    /**
     * @Route("map/{id}/user/{userId}/system/{systemKey}")
     * @Method({"DELETE", "OPTIONS"})
     *
     * @param string $userId
     * @param string $systemKey
     *
     * @return Response
     */
    public function deleteAction(string $id, string $userId, string $systemKey): Response
    {
        try {
            $this->handler->delete($id, $userId, $systemKey);

            return new JsonResponse([], 200);
        } catch (Throwable $e) {
            return self::processException($e);
        }
    }

    /**
     * @param Throwable $e
     *
     * @return Response
     */
    private static function processException(Throwable $e): Response
    {
        $code      = 500;
        $className = get_class($e);

        if ($className === SystemException::class) {
            $sysNotFound = [
                SystemException::SYSTEM_NOT_FOUND,
                SystemException::SYSTEM_OR_USER_NOT_FOUND,
                SystemException::SYSTEM_PROPERTY_NOT_FOUND,
            ];
            if (in_array($e->getCode(), $sysNotFound)) {
                $code = 404;
            }
        } else if ($className === LogicException::class) {
            $code = 404;
        } else if ($className === CleverConnectorsException::class || $className === PipesFrameworkException::class) {
            $code = 400;
            if ($className === CleverConnectorsException::class &&
                $e->getCode() == CleverConnectorsException::MAP_TEMPLATE_NOT_FOUND
            ) {
                $code = 404;
            }
        }

        return new Response(json_encode([
            'status'  => 'ERROR',
            'message' => $e->getMessage(),
        ]), $code);
    }

}