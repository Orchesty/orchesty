<?php declare(strict_types=1);

namespace Hanaboso\Applinth\Controller;

use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\Applinth\Authenticator\EndUserAuthenticator;
use Hanaboso\Applinth\Handler\AuthorizationHandler;
use Hanaboso\Utils\Exception\DateTimeException;
use Hanaboso\Utils\System\ControllerUtils;
use Hanaboso\Utils\Traits\ControllerTrait;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * Class AuthorizationController
 *
 * @package Hanaboso\Applinth\Controller
 *
 * @Route("/authorization")
 */
final class AuthorizationController extends AbstractController
{

    use ControllerTrait;

    private const ACCESS_TOKEN  = 'access_token';
    private const EXPIRES_IN    = 'expires_in';
    private const REDIRECT_LINK = 'oauth_redirect_link';

    /**
     * AuthorizationController constructor.
     *
     * @param AuthorizationHandler $authorizationHandler
     */
    public function __construct(private readonly AuthorizationHandler $authorizationHandler)
    {
    }

    /**
     * @route("/login", methods={"POST"})
     *
     * @param Request $request
     *
     * @return Response
     * @throws MongoDBException
     * @throws DateTimeException
     */
    public function login(Request $request): Response
    {
        try {
            $jweToken                = $this->checkAndGetTokenFromRequest($request);
            $jwePayload              = $this->authorizationHandler->payloadFromJwe($jweToken);
            [$jwsToken, $expiration] = $this->authorizationHandler->jwsFromJwe($jwePayload);
        } catch (AuthenticationException $e) {
            return $this->getErrorResponse($e, Response::HTTP_FORBIDDEN, ControllerUtils::NOT_ALLOWED);
        } catch (LogicException $e) {
            return $this->getErrorResponse($e, Response::HTTP_BAD_REQUEST, ControllerUtils::INVALID_REQUEST);
        }

        $this->authorizationHandler->saveRestrictToken($jweToken);
        $link = $this->authorizationHandler->initRootApp($jwePayload);

        return $this->getResponse(
            [
                self::ACCESS_TOKEN  => $jwsToken,
                self::EXPIRES_IN    => $expiration,
                self::REDIRECT_LINK => $link,
            ],
        );
    }

    /**
     * @route("/logged", methods={"GET"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function logged(Request $request): Response
    {
        try {
            [$jwsToken, $expiration] = $this->renewToken($request);
        } catch (LogicException $e) {
            return $this->getErrorResponse($e, Response::HTTP_BAD_REQUEST, ControllerUtils::INVALID_REQUEST);
        }

        return $this->getResponse([self::ACCESS_TOKEN => $jwsToken, self::EXPIRES_IN => $expiration]);
    }

    /**
     * @param Request $request
     *
     * @return string[]
     */
    private function renewToken(Request $request): array
    {
        if ($request->headers->has(EndUserAuthenticator::AUTHORIZATION)) {
            $jwsToken = $request->headers->get(EndUserAuthenticator::AUTHORIZATION) ?? '';

            return $this->authorizationHandler->jwsFromJws($jwsToken);
        }

        throw new LogicException('Token is missing');
    }

    /**
     * @param Request $request
     *
     * @return string
     */
    private function checkAndGetTokenFromRequest(Request $request): string
    {
        if ($request->headers->has(EndUserAuthenticator::AUTHORIZATION)) {
            $token = $request->headers->get(EndUserAuthenticator::AUTHORIZATION) ?? '';
            if ($this->authorizationHandler->isTokenExits($token)) {
                throw new AuthenticationException('Token is not valid.');
            }

            return $token;
        }

        throw new LogicException('Token is missing');
    }

}
