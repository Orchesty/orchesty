<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\User\Model\User;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManager;
use Hanaboso\PipesFramework\Acl\Enum\ResourceEnum;
use Hanaboso\PipesFramework\HbPFAclBundle\Provider\ResourceProvider;
use Hanaboso\PipesFramework\User\DatabaseManager\UserDatabaseManagerLocator;
use Hanaboso\PipesFramework\User\Document\User as OdmUser;
use Hanaboso\PipesFramework\User\Entity\TmpUserInterface;
use Hanaboso\PipesFramework\User\Entity\User as OrmUser;
use Hanaboso\PipesFramework\User\Entity\UserInterface;
use Hanaboso\PipesFramework\User\Model\Security\SecurityManager;
use Hanaboso\PipesFramework\User\Model\Token\TokenManager;
use Hanaboso\PipesFramework\User\Model\User\Event\UserEvent;
use Hanaboso\PipesFramework\User\Repository\Document\TmpUserRepository as OdmTmpRepo;
use Hanaboso\PipesFramework\User\Repository\Document\UserRepository as OdmRepo;
use Hanaboso\PipesFramework\User\Repository\Entity\TmpUserRepository as OrmTmpRepo;
use Hanaboso\PipesFramework\User\Repository\Entity\UserRepository as OrmRepo;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

/**
 * Class UserManager
 *
 * @package Hanaboso\PipesFramework\User\Model\User
 */
class UserManager
{

    /**
     * @var DocumentManager|EntityManager
     */
    private $dm;

    /**
     * @var SecurityManager
     */
    private $securityManager;

    /**
     * @var TokenManager
     */
    private $tokenManager;

    /**
     * @var OdmRepo|OrmRepo
     */
    private $userRepository;

    /**
     * @var OdmTmpRepo|OrmTmpRepo
     */
    private $tmpUserRepository;

    /**
     * @var PasswordEncoderInterface
     */
    private $encoder;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var ResourceProvider
     */
    private $provider;

    /**
     * UserManager constructor.
     *
     * @param UserDatabaseManagerLocator $userDml
     * @param SecurityManager            $securityManager
     * @param TokenManager               $tokenManager
     * @param EncoderFactory             $encoderFactory
     * @param EventDispatcherInterface   $eventDispatcher
     * @param ResourceProvider           $provider
     */
    public function __construct(
        UserDatabaseManagerLocator $userDml,
        SecurityManager $securityManager,
        TokenManager $tokenManager,
        EncoderFactory $encoderFactory,
        EventDispatcherInterface $eventDispatcher,
        ResourceProvider $provider
    )
    {
        $this->dm                = $userDml->get();
        $this->securityManager   = $securityManager;
        $this->tokenManager      = $tokenManager;
        $this->userRepository    = $this->dm->getRepository($provider->getResource(ResourceEnum::USER));
        $this->tmpUserRepository = $this->dm->getRepository($provider->getResource(ResourceEnum::TMP_USER));
        $this->encoder           = $encoderFactory->getEncoder($provider->getResource(ResourceEnum::USER));
        $this->eventDispatcher   = $eventDispatcher;
        $this->provider          = $provider;
    }

    /**
     * @param array $data
     *
     * @return UserInterface
     */
    public function login(array $data): UserInterface
    {
        $user = $this->securityManager->login($data);
        $this->eventDispatcher->dispatch(UserEvent::USER_LOGIN, new UserEvent($user));

        return $user;
    }

    /**
     *
     */
    public function logout(): void
    {
        $this->eventDispatcher->dispatch(
            UserEvent::USER_LOGOUT,
            new UserEvent($this->securityManager->getLoggedUser())
        );
        $this->securityManager->logout();
    }

    /**
     * @param array $data
     *
     * @throws UserManagerException
     */
    public function register(array $data): void
    {
        if ($this->userRepository->findOneBy(['email' => $data['email']])) {
            throw new UserManagerException(
                sprintf('Email \'%s\' already exists.', $data['email']),
                UserManagerException::USER_EMAIL_ALREADY_EXISTS
            );
        }

        $user = $this->tmpUserRepository->findOneBy(['email' => $data['email']]);

        if (!$user) {
            $class = $this->provider->getResource(ResourceEnum::TMP_USER);
            /** @var TmpUserInterface $user */
            $user = new $class();
            $user->setEmail($data['email']);
            $this->dm->persist($user);
            $this->dm->flush();
        }

        $this->tokenManager->create($user); // TODO: Send token by email
        $this->eventDispatcher->dispatch(UserEvent::USER_REGISTER, new UserEvent($user));
    }

    /**
     * @param string $id
     */
    public function activate(string $id): void
    {
        $token = $this->tokenManager->validate($id);

        /** @var OdmUser|OrmUser $class */
        $class = $this->provider->getResource(ResourceEnum::USER);
        $user  = $class::from($token->getTmpUser());
        $this->dm->persist($user);
        $this->eventDispatcher->dispatch(UserEvent::USER_ACTIVATE, new UserEvent($user, NULL, $token->getTmpUser()));

        $this->dm->remove($token->getTmpUser());
        $token->setUser($user)->setTmpUser(NULL);
        $this->dm->flush();

        // TODO: Send notification by email
    }

    /**
     * @param string $id
     * @param array  $data
     */
    public function setPassword(string $id, array $data): void
    {
        $token = $this->tokenManager->validate($id);
        $token
            ->getUserOrTmpUser()
            ->setPassword($this->encoder->encodePassword($data['password'], ''))
            ->setToken(NULL);

        $this->dm->remove($token);
        $this->dm->flush();
    }

    /**
     * @param array $data
     */
    public function changePassword(array $data): void
    {
        $loggedUser = $this->securityManager->getLoggedUser();
        $this->eventDispatcher->dispatch(UserEvent::USER_CHANGE_PASSWORD, new UserEvent($loggedUser));

        $loggedUser->setPassword($this->encoder->encodePassword($data['password'], ''));
        $this->dm->flush();
    }

    /**
     * @param array $data
     *
     * @throws UserManagerException
     */
    public function resetPassword(array $data): void
    {
        /** @var UserInterface $user */
        $user = $this->userRepository->findOneBy(['email' => $data['email']]);

        if (!$user) {
            throw new UserManagerException(
                sprintf('Email \'%s\' not exists.', $data['email']),
                UserManagerException::USER_EMAIL_NOT_EXISTS
            );
        }

        $this->tokenManager->create($user); // TODO: Send token by email
        $this->eventDispatcher->dispatch(UserEvent::USER_RESET_PASSWORD, new UserEvent($user));
    }

    /**
     * @param UserInterface|OdmUser|OrmUser $user
     *
     * @return UserInterface
     * @throws UserManagerException
     */
    public function delete($user): UserInterface
    {
        $this->eventDispatcher->dispatch(
            UserEvent::USER_DELETE_BEFORE,
            new UserEvent($user, $this->securityManager->getLoggedUser())
        );

        if ($this->securityManager->getLoggedUser()->getId() === $user->getId()) {
            throw new UserManagerException(
                sprintf('User \'%s\' delete not allowed.', $user->getId()),
                UserManagerException::USER_DELETE_NOT_ALLOWED
            );
        }

        $user->setDeleted(TRUE);
        $this->dm->flush();
        $this->eventDispatcher->dispatch(
            UserEvent::USER_DELETE_AFTER,
            new UserEvent($user, $this->securityManager->getLoggedUser())
        );

        return $user;
    }

}