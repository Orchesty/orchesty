<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Authorization\Wrapper;

use Exception;
use Hanaboso\PipesPhpSdk\Authorization\Exception\AuthorizationException;
use League\OAuth2\Client\Provider\GenericProvider;
use Psr\Http\Message\RequestInterface;

/**
 * Class OAuth2Wrapper
 *
 * @package Hanaboso\PipesPhpSdk\Authorization\Wrapper
 */
class OAuth2Wrapper extends GenericProvider
{

    /**
     * OAuth2Wrapper constructor.
     *
     * @param array $options
     * @param array $collaborators
     */
    public function __construct(array $options = [], array $collaborators = [])
    {
        parent::__construct($options, $collaborators);
    }

    /**
     * @param RequestInterface $request
     *
     * @return array
     * @throws AuthorizationException
     */
    public function getParsedResponse(RequestInterface $request): array
    {
        try {
            $res = parent::getParsedResponse($request);
        } catch (Exception $e) {
            $res = $e->getMessage();
        }

        if (!is_array($res)) {
            throw new AuthorizationException(
                $res,
                AuthorizationException::AUTHORIZATION_RESPONSE_ARRAY_EXPECTED
            );
        }

        return $res;
    }

}
