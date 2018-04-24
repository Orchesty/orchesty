<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Controller;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Handler\LayoutHandler;
use CleverConnectors\AppBundle\Model\DataLayout\Exceptions\LayoutException;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\CommonsBundle\Exception\PipesFrameworkException;
use Hanaboso\CommonsBundle\Traits\ControllerTrait;
use LogicException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Class LayoutController
 *
 * @package CleverConnectors\AppBundle\Controller
 *
 * @Route(service="cc.layout.controller")
 */
class LayoutController extends FOSRestController
{

    use ControllerTrait;

    /**
     * @var LayoutHandler
     */
    private $handler;

    /**
     * SystemController constructor.
     *
     * @param LayoutHandler $handler
     */
    public function __construct(LayoutHandler $handler)
    {
        $this->handler = $handler;
    }

    /**
     * @Route("layout/user/{userId}/system/{systemKey}")
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
            return $this->getResponse($this->handler->create($userId, $systemKey, $request->request->all()));
        } catch (Throwable $e) {
            return $this->processException($e);
        }
    }

    /**
     * @Route("layout/{id}/user/{userId}/system/{systemKey}")
     * @Method({"PUT", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $id
     * @param string  $userId
     * @param string  $systemKey
     *
     * @return Response
     */
    public function updateAction(Request $request, string $id, string $userId, string $systemKey): Response
    {
        try {
            return $this->getResponse($this->handler->update($id, $userId, $systemKey, $request->request->all()));
        } catch (Throwable $e) {
            return $this->processException($e);
        }
    }

    /**
     * @Route("layout/{id}/user/{userId}/system/{systemKey}")
     * @Method({"DELETE", "OPTIONS"})
     *
     * @param string $id
     * @param string $userId
     * @param string $systemKey
     *
     * @return Response
     */
    public function deleteAction(string $id, string $userId, string $systemKey): Response
    {
        try {
            $this->handler->delete($id, $userId, $systemKey);

            return $this->getResponse([]);
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
            ];
            if (in_array($e->getCode(), $sysNotFound)) {
                $code = 404;
            }
        } else if ($className === LogicException::class) {
            $code = 404;
        } else if ($className === CleverConnectorsException::class || $className === PipesFrameworkException::class) {
            $code = 400;
            if ($className === CleverConnectorsException::class &&
                $e->getCode() == CleverConnectorsException::DATALAYOUT_NOT_FOUND
            ) {
                $code = 404;
            }
        } else if ($className === LayoutException::class && $e->getCode() == LayoutException::DATA_LAYOUT_ALREADY_EXISTS) {
            $code = 400;
        }

        return $this->getErrorResponse($e, $code);
    }

}