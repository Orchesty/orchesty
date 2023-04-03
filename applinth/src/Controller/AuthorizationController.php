<?php declare(strict_types=1);

namespace Hanaboso\Applinth\Controller;

use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\Applinth\Authenticator\EndUserAuthenticator;
use Hanaboso\Applinth\Handler\AuthorizationHandler;
use Hanaboso\UserBundle\Model\Security\SecurityManagerException;
use Hanaboso\Utils\Exception\DateTimeException;
use Hanaboso\Utils\Exception\PipesFrameworkException;
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
    private const REFRESH_TOKEN = 'refresh_token';
    private const EXPIRES_IN    = 'expires_in';
    private const REDIRECT_LINK = 'oauth_redirect_link';

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
     * @route("/login", methods={"POST"})
     *
     * @param Request $request
     *
     * @return Response
     * @throws DateTimeException
     * @throws MongoDBException
     * @throws SecurityManagerException
     * @throws PipesFrameworkException
     */
    public function login(Request $request): Response
    {
        try {
            $jweToken                           = $this->checkAndGetTokenFromRequest($request);
            $jwePayload                         = $this->authorizationHandler->payloadFromJwe($jweToken);
            [$refreshToken, $refreshExpiration] = $this->authorizationHandler->jwsFromJwe($jwePayload, 7_200);
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
                'secure'   => $request->isSecure(),
                'expires'  => intval($refreshExpiration),
                'samesite' => $this->sameSite,
                'httponly' => TRUE,
            ],
        );

        $this->authorizationHandler->saveRestrictToken($jweToken);
        $link = $this->authorizationHandler->initRootApp($this->authorizationHandler->payloadFromJwe($jweToken, TRUE));

        return $this->getResponse(
            [
                self::ACCESS_TOKEN  => $accessToken,
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
        if ($request->cookies->has(self::REFRESH_TOKEN)) {
            $jwsToken = $request->cookies->get(self::REFRESH_TOKEN);

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
