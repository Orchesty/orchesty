<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Authorization\Provider;

use Hanaboso\PipesPhpSdk\Application\Utils\ApplicationUtils;
use Hanaboso\PipesPhpSdk\Authorization\Exception\AuthorizationException;
use Psr\Log\LoggerInterface;

/**
 * Class OAuthProviderAbstract
 *
 * @package Hanaboso\PipesPhpSdk\Authorization\Provider
 */
abstract class OAuthProviderAbstract implements OAuthProviderInterface
{

    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * OAuthProviderAbstract constructor.
     *
     * @param string $backend
     */
    public function __construct(protected string $backend)
    {
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @return string
     */
    public function getRedirectUri(): string
    {
        return sprintf('%s/%s', rtrim($this->backend, '/'), ltrim(ApplicationUtils::generateUrl(), '/'));
    }

    /**
     * @param string $message
     * @param int    $code
     *
     * @throws AuthorizationException
     */
    protected function throwException(string $message, int $code): void
    {
        $this->logger->error($message);

        throw new AuthorizationException($message, $code);
    }

}
