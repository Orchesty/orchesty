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
 */
#[Route('/authorization')]
final class AuthorizationController extends AbstractController
{

    use ControllerTrait;

    private const ACCESS_TOKEN  = 'access_token';
    private const REFRESH_TOKEN = 'refresh_token';
    private const EXPIRES_IN    = 'expires_in';
    private const REDIRECT_LINK = 'oauth_redirect_link';
    private const SCOPE         = 'scope';

    /**
     * AuthorizationController constructor.
     *
     * @phpstan-param 'None'|'Lax'|'Strict' $sameSite
     *
     * @param AuthorizationHandler $authorizationHandler
     * @param string               $sameSite
     */
    public function __construct(
        private readonly AuthorizationHandler $authorizationHandler,
        private readonly string $sameSite,
    )
    {
    }

    /**
     * @param Request $request
     *
     * @return Response
     * @throws DateTimeException
     * @throws MongoDBException
     */
    #[Route('/login', methods: ['POST'])]
    public function login(Request $request): Response
    {
        try {
            $jweToken                           = $this->checkAndGetTokenFromRequest($request);
            $jwePayload                         = $this->authorizationHandler->payloadFromJwe($jweToken);
            [$refreshToken, $refreshExpiration] = $this->authorizationHandler->jwsFromJwe($jwePayload, NULL);
            [$accessToken, $expiration]         = $this->authorizationHandler->jwsFromJwe($jwePayload);
        } catch (AuthenticationException $e) {
            return $this->getErrorResponse($e, Response::HTTP_FORBIDDEN, ControllerUtils::NOT_ALLOWED);
        } catch (LogicException $e) {
            return $this->getErrorResponse($e, Response::HTTP_BAD_REQUEST, ControllerUtils::INVALID_REQUEST);
        }

        setcookie(
            self::REFRESH_TOKEN,
            $refreshToken,
            [
                'expires'  => intval($refreshExpiration),
                'httponly' => TRUE,
                'samesite' => $this->sameSite,
                'secure'   => $request->isSecure(),
            ],
        );

        $this->authorizationHandler->saveRestrictToken($jweToken);
        $link = $this->authorizationHandler->initRootApp($this->authorizationHandler->payloadFromJwe($jweToken, TRUE));

        $res = [
            self::ACCESS_TOKEN  => $accessToken,
            self::EXPIRES_IN    => $expiration,
            self::REDIRECT_LINK => $link,
        ];
        if ($request->query->get(self::SCOPE) === self::REFRESH_TOKEN) {
            $res = array_merge($res, [self::REFRESH_TOKEN => $refreshToken]);
        }

        return $this->getResponse($res);
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/refresh', methods: ['POST'])]
    public function refresh(Request $request): Response
    {
        return $this->logged($request);
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/logged', methods: ['GET'])]
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
        $jwsToken = NULL;
        if ($request->cookies->has(self::REFRESH_TOKEN)) {
            $jwsToken = $request->cookies->get(self::REFRESH_TOKEN);
        }

        if ($request->request->has(self::REFRESH_TOKEN)) {
            $jwsToken = $request->request->get(self::REFRESH_TOKEN);
        }

        if ($jwsToken) {
            return $this->authorizationHandler->jwsFromJws((string) $jwsToken);
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
