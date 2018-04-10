<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\CM;

/**
 * Created by PhpStorm.
 * User: radekj
 * Date: 21.9.17
 * Time: 17:48
 */

/**
 * Class CMAuthorization
 *
 * @package CleverConnectors\AppBundle\Model\CM
 */
abstract class CMAuthorization
{

    /**
     * @param string $user
     * @param string $token
     * @param array  $headers
     *
     * @return array
     */
    protected function getAuthorizationHeaders(string $user, string $token, array $headers = []): array
    {
        $headers['Accept']       = 'application/json';
        $headers['Content-type'] = 'application/json';
        $headers['X-Api-Key']    = sprintf('%s:%s', $user, $token);

        return $headers;
    }

    /**
     * @return string
     */
    protected function getBaseUrl(): string
    {
        return 'https://api.dev.clevermonitor.com/v1.2';
    }

}