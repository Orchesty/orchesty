<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\User\Model\User;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Hanaboso\PipesFramework\User\Document\TmpUser;
use Hanaboso\PipesFramework\User\Document\User;
use Hanaboso\PipesFramework\User\Model\Security\SecurityManager;
use Hanaboso\PipesFramework\User\Model\Token\TokenManager;
use Hanaboso\PipesFramework\User\Repository\TmpUserRepository;
use Hanaboso\PipesFramework\User\Repository\UserRepository;
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
     * @var DocumentManager
     */
    private $documentManager;

    /**
     * @var SecurityManager
     */
    private $securityManager;

    /**
     * @var TokenManager
     */
    private $tokenManager;

    /**
     * @var UserRepository|DocumentRepository
     */
    private $userRepository;

    /**
     * @var TmpUserRepository|DocumentRepository
     */
    private $tmpUserRepository;

    /**
     * @var PasswordEncoderInterface
     */
    private $encoder;

    /**
     * UserManager constructor.
     *
     * @param DocumentManager $documentManager
     * @param SecurityManager $securityManager
     * @param TokenManager    $tokenManager
     * @param EncoderFactory  $encoderFactory
     */
    public function __construct(
        DocumentManager $documentManager,
        SecurityManager $securityManager,
        TokenManager $tokenManager,
        EncoderFactory $encoderFactory
    )
    {
        $this->documentManager   = $documentManager;
        $this->securityManager   = $securityManager;
        $this->tokenManager      = $tokenManager;
        $this->userRepository    = $documentManager->getRepository(User::class);
        $this->tmpUserRepository = $documentManager->getRepository(TmpUser::class);
        $this->encoder           = $encoderFactory->getEncoder(User::class);
    }

    /**
     * @param array $data
     *
     * @return User
     */
    public function login(array $data): User
    {
        return $this->securityManager->login($data);
    }

    /**
     *
     */
    public function logout(): void
    {
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
            $user = (new TmpUser())->setEmail($data['email']);
            $this->documentManager->persist($user);
            $this->documentManager->flush();
        }

        $this->tokenManager->create($user); // TODO: Send token by email
    }

    /**
     * @param string $id
     */
    public function activate(string $id): void
    {
        $token = $this->tokenManager->validate($id);

        $user = User::from($token->getUserOrTmpUser());
        $this->documentManager->remove($token->getUserOrTmpUser());
        $this->documentManager->persist($user);
        $this->documentManager->flush();

        $token->setUser($user)->setTmpUser(NULL);
        $this->documentManager->flush();

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
            ->setPassword($this->encoder->encodePassword($data['password'], ''));

        $this->documentManager->remove($token);
        $this->documentManager->flush();
    }

    /**
     * @param array $data
     *
     * @throws UserManagerException
     */
    public function resetPassword(array $data): void
    {
        $user = $this->userRepository->findOneBy(['email' => $data['email']]);

        if (!$user) {
            throw new UserManagerException(
                sprintf('Email \'%s\' not exists.', $data['email']),
                UserManagerException::USER_EMAIL_NOT_EXISTS
            );
        }

        $this->tokenManager->create($user); // TODO: Send token by email
    }

}