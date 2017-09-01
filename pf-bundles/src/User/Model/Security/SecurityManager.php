<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\User\Model\Security;

use Hanaboso\PipesFramework\Acl\Enum\ResourceEnum;
use Hanaboso\PipesFramework\HbPFAclBundle\Provider\ResourceProvider;
use Hanaboso\PipesFramework\User\DatabaseManager\UserDatabaseManagerLocator;
use Hanaboso\PipesFramework\User\Entity\UserInterface;
use Hanaboso\PipesFramework\User\Model\Token;
use Hanaboso\PipesFramework\User\Repository\Document\UserRepository as OdmRepo;
use Hanaboso\PipesFramework\User\Repository\Entity\UserRepository as OrmRepo;
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
     * @var OrmRepo|OdmRepo
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
     * @var ResourceProvider
     */
    private $provider;

    /**
     * SecurityManager constructor.
     *
     * @param UserDatabaseManagerLocator $userDml
     * @param EncoderFactory             $encoderFactory
     * @param Session                    $session
     * @param TokenStorage               $tokenStorage
     * @param ResourceProvider           $provider
     */
    public function __construct(
        UserDatabaseManagerLocator $userDml,
        EncoderFactory $encoderFactory,
        Session $session,
        TokenStorage $tokenStorage,
        ResourceProvider $provider
    )
    {
        $this->userRepository = $userDml->get()->getRepository($provider->getResource(ResourceEnum::USER));
        $this->encoderFactory = $encoderFactory;
        $this->tokenStorage   = $tokenStorage;
        $this->session        = $session;
        $this->sessionName    = self::SECURITY_KEY . self::SECURED_AREA;
        $this->provider = $provider;
    }

    /**
     * @param array $data
     *
     * @return UserInterface
     * @throws SecurityManagerException
     */
    public function login(array $data): UserInterface
    {
        if ($this->isLoggedIn()) {
            /** @var UserInterface $user */
            $user = $this->userRepository->find($this->session->get($this->sessionName));

            return $user;
        }

        /** @var UserInterface $user */
        $user = $this->userRepository->findOneBy(['email' => $data['email']]);

        if (!$user) {
            throw new SecurityManagerException(
                sprintf('User \'%s\' or password not valid.', $data['email']),
                SecurityManagerException::USER_OR_PASSWORD_NOT_VALID
            );
        }

        $encoder = $this->encoderFactory->getEncoder($this->provider->getResource(ResourceEnum::USER));

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
     * @return UserInterface
     * @throws SecurityManagerException
     */
    public function getLoggedUser(): UserInterface
    {
        if (!$this->isLoggedIn()) {
            throw new SecurityManagerException(
                'User not logged.',
                SecurityManagerException::USER_NOT_LOGGED
            );

        }

        /** @var Token $token */
        $token = unserialize($this->session->get($this->sessionName));

        /** @var UserInterface $user */
        $user = $this->userRepository->find($token->getUser()->getId());

        return $user;
    }

}