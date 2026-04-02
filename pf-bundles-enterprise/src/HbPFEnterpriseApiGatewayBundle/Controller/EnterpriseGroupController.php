<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseApiGatewayBundle\Controller;

use Exception;
use Hanaboso\AclBundle\Exception\AclException;
use Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Handler\EnterpriseGroupHandler;
use Hanaboso\Utils\Traits\ControllerTrait;
use InvalidArgumentException;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Class EnterpriseGroupController
 *
 * @package Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseApiGatewayBundle\Controller
 */
final class EnterpriseGroupController
{

    use ControllerTrait;

    /**
     * EnterpriseGroupController constructor.
     *
     * @param EnterpriseGroupHandler $groupHandler
     */
    public function __construct(private readonly EnterpriseGroupHandler $groupHandler)
    {
        $this->logger = new NullLogger();
    }

    /**
     * @return Response
     */
    #[Route('/group/list', methods: ['GET'], priority: 10)]
    public function listAction(): Response
    {
        try {
            return $this->getResponse($this->groupHandler->listGroups());
        } catch (Exception $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @param string $id
     *
     * @return Response
     */
    #[Route('/group/{id}', methods: ['GET'], priority: 10)]
    public function detailAction(string $id): Response
    {
        try {
            return $this->getResponse($this->groupHandler->getGroup($id));
        } catch (InvalidArgumentException $e) {
            return $this->getErrorResponse($e, 404);
        } catch (Exception $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/group', methods: ['POST'], priority: 10)]
    public function createAction(Request $request): Response
    {
        try {
            $name = $request->request->getString('name');
            if ($name === '') {
                return $this->getErrorResponse(new InvalidArgumentException('Missing parameter "name"'), 400);
            }

            $level = $request->request->getInt('level', 999);

            return $this->getResponse($this->groupHandler->createGroup($name, $level));
        } catch (AclException $e) {
            return $this->getErrorResponse($e, 400);
        } catch (Exception $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @param string  $id
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/group/{id}', methods: ['PUT'], priority: 10)]
    public function updateAction(string $id, Request $request): Response
    {
        try {
            $name  = $request->request->getString('name') ?: NULL;
            $level = $request->request->has('level') ? $request->request->getInt('level') : NULL;

            return $this->getResponse($this->groupHandler->updateGroup($id, $name, $level));
        } catch (InvalidArgumentException $e) {
            return $this->getErrorResponse($e, 404);
        } catch (AclException $e) {
            return $this->getErrorResponse($e, 400);
        } catch (Exception $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @param string $id
     *
     * @return Response
     */
    #[Route('/group/{id}', methods: ['DELETE'], priority: 10)]
    public function deleteAction(string $id): Response
    {
        try {
            $this->groupHandler->deleteGroup($id);

            return $this->getResponse([]);
        } catch (InvalidArgumentException $e) {
            return $this->getErrorResponse($e, 404);
        } catch (AclException $e) {
            return $this->getErrorResponse($e, 400);
        } catch (Exception $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @param string $userId
     *
     * @return Response
     */
    #[Route('/user/{userId}/groups', methods: ['GET'], priority: 10)]
    public function userGroupsAction(string $userId): Response
    {
        try {
            return $this->getResponse($this->groupHandler->getUserGroups($userId));
        } catch (InvalidArgumentException $e) {
            return $this->getErrorResponse($e, 404);
        } catch (Exception $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @param string $id
     * @param string $userId
     *
     * @return Response
     */
    #[Route('/group/{id}/user/{userId}', methods: ['POST'], priority: 10)]
    public function addUserAction(string $id, string $userId): Response
    {
        try {
            $this->groupHandler->addUserToGroup($id, $userId);

            return $this->getResponse([]);
        } catch (InvalidArgumentException $e) {
            return $this->getErrorResponse($e, 404);
        } catch (AclException $e) {
            return $this->getErrorResponse($e, 400);
        } catch (Exception $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @param string $id
     * @param string $userId
     *
     * @return Response
     */
    #[Route('/group/{id}/user/{userId}', methods: ['DELETE'], priority: 10)]
    public function removeUserAction(string $id, string $userId): Response
    {
        try {
            $this->groupHandler->removeUserFromGroup($id, $userId);

            return $this->getResponse([]);
        } catch (InvalidArgumentException $e) {
            return $this->getErrorResponse($e, 404);
        } catch (AclException $e) {
            return $this->getErrorResponse($e, 400);
        } catch (Exception $e) {
            return $this->getErrorResponse($e);
        }
    }

}
