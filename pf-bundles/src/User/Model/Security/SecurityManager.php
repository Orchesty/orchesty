<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\User\Model\Security;

use Hanaboso\PipesFramework\User\DatabaseManager\UserDatabaseManagerLocator;
use Hanaboso\PipesFramework\User\Document\User;
use Hanaboso\PipesFramework\User\Model\Token;
use Hanaboso\PipesFramework\User\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;

/**
 * Class SecurityManager
 *
 * @package Hanaboso\PipesFramework\User\Model\Security
 */
class SecurityManager
{

    public const SECURITY_KEY = '_security_';
    public const SECURED_AREA = 'secured_area';

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var EncoderFactory
     */
    private $encoderFactory;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var TokenStorage
     */
    private $tokenStorage;

    /**
     * @var string
     */
    private $sessionName;

    /**
     * SecurityManager constructor.
     *
     * @param UserDatabaseManagerLocator $databaseManagerLocator
     * @param EncoderFactory             $encoderFactory
     * @param Session                    $session
     * @param TokenStorage               $tokenStorage
     */
    public function __construct(
        UserDatabaseManagerLocator $databaseManagerLocator,
        EncoderFactory $encoderFactory,
        Session $session,
        TokenStorage $tokenStorage
    )
    {
        $this->userRepository = $databaseManagerLocator->get()->getRepository(User::class);
        $this->encoderFactory = $encoderFactory;
        $this->tokenStorage   = $tokenStorage;
        $this->session        = $session;
        $this->sessionName    = self::SECURITY_KEY . self::SECURED_AREA;
    }

    /**
     * @param array $data
     *
     * @return User
     * @throws SecurityManagerException
     */
    public function login(array $data): User
    {
        if ($this->isLoggedIn()) {
            /** @var User $user */
            $user = $this->userRepository->find($this->session->get($this->sessionName));

            return $user;
        }

        /** @var User $user */
        $user = $this->userRepository->findOneBy(['email' => $data['email']]);

        if (!$user) {
            throw new SecurityManagerException(
                sprintf('User \'%s\' or password not valid.', $data['email']),
                SecurityManagerException::USER_OR_PASSWORD_NOT_VALID
            );
        }

        $encoder = $this->encoderFactory->getEncoder(User::class);

        if (!$encoder) {
            throw new SecurityManagerException(
                sprintf('User \'%s\' encoder not found.', $data['email']),
                SecurityManagerException::USER_ENCODER_NOT_FOUND
            );
        }

        if (!$encoder->isPasswordValid($user->getPassword(), $data['password'], '')) {
            throw new SecurityManagerException(
                sprintf('User \'%s\' or password not valid.', $data['email']),
                SecurityManagerException::USER_OR_PASSWORD_NOT_VALID
            );
        }

        $token = new Token($user, $data['password'], self::SECURED_AREA);
        $this->tokenStorage->setToken($token);
        $this->session->set($this->sessionName, serialize($token));

        return $user;
    }

    /**
     *
     */
    public function logout(): void
    {
        $this->session->invalidate();
        $this->session->clear();
    }

    /**
     * @return bool
     */
    public function isLoggedIn(): bool
    {
        return $this->session->has($this->sessionName);
    }

    /**
     * @return User
     * @throws SecurityManagerException
     */
    public function getLoggedUser(): User
    {
        if (!$this->isLoggedIn()) {
            throw new SecurityManagerException(
                'User not logged.',
                SecurityManagerException::USER_NOT_LOGGED
            );

        }

        /** @var Token $token */
        $token = unserialize($this->session->get($this->sessionName));

        /** @var User $user */
        $user = $this->userRepository->find($token->getUser()->getId());

        return $user;
    }

}