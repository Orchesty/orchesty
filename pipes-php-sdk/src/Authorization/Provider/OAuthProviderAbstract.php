<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Authorization\Provider;

use Hanaboso\CommonsBundle\Redirect\RedirectInterface;
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
     * @var RedirectInterface
     */
    protected $redirect;

    /**
     * @var string
     */
    protected $backend;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * OAuthProviderAbstract constructor.
     *
     * @param RedirectInterface $redirect
     * @param string            $backend
     */
    public function __construct(RedirectInterface $redirect, string $backend)
    {
        $this->redirect = $redirect;
        $this->backend  = $backend;
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return $this
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
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
     *
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
