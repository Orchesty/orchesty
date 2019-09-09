<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Authorization\Provider;

use Hanaboso\CommonsBundle\Redirect\RedirectInterface;
use Hanaboso\PipesPhpSdk\Authorization\Exception\AuthorizationException;
use Psr\Log\LoggerInterface;

/**
 * Class OAuthProviderAbstract
 *
 * @package Hanaboso\PipesPhpSdk\Authorization\Provider
 */
class OAuthProviderAbstract
{

    /**
     * @var RedirectInterface
     */
    protected $redirect;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * OAuthProviderAbstract constructor.
     *
     * @param RedirectInterface $redirect
     */
    public function __construct(RedirectInterface $redirect)
    {
        $this->redirect = $redirect;
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