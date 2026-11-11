<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller;

use Hanaboso\PipesFramework\ApiGateway\Action\ActionException;
use Hanaboso\PipesFramework\ApiGateway\Action\ActionManager;
use Hanaboso\Utils\Traits\ControllerTrait;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

/**
 * Class ActionController
 *
 * @package Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller
 */
final class ActionController extends AbstractController
{

    use ControllerTrait;

    // Header with the worker's Orchesty API key (the SDK sends `orchesty-api-key`)
    public const array API_KEY_HEADERS = ['orchesty-api-key', 'X-Auth'];

    /**
     * ActionController constructor.
     *
     * @param ActionManager $manager
     */
    public function __construct(private readonly ActionManager $manager)
    {
        $this->logger = new NullLogger();
    }

    /**
     * @return Response
     */
    #[Route('/actions', methods: ['GET'])]
    public function listAction(): Response
    {
        try {
            return $this->getResponse(['items' => $this->manager->getCatalog()]);
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * Authorization decision for a worker about to run an Action on behalf of an identity.
     * Not behind the user firewall: the worker authenticates with its API key.
     *
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/actions/grant', methods: ['POST'])]
    public function grantAction(Request $request): Response
    {
        try {
            return $this->getResponse($this->manager->grant(self::getApiKey($request), $request->request->all()));
        } catch (ActionException $e) {
            return $this->getErrorResponse($e, $e->getCode());
        } catch (Throwable $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     * @param Request $request
     *
     * @return string|null
     */
    public static function getApiKey(Request $request): ?string
    {
        foreach (self::API_KEY_HEADERS as $header) {
            $value = $request->headers->get($header);
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return NULL;
    }

}
