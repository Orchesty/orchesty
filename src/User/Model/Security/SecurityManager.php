<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\User\Model\Security;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentRepository;
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

    /**
     * @var UserRepository|DocumentRepository
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
     * SecurityManager constructor.
     *
     * @param DocumentManager $documentManager
     * @param EncoderFactory  $encoderFactory
     */
    public function __construct(DocumentManager $documentManager, EncoderFactory $encoderFactory, Session $session)
    {
        $this->userRepository = $documentManager->getRepository(User::class);
        $this->encoderFactory = $encoderFactory;
        $this->session        = $session;
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
            return $this->userRepository->find($this->session->get('loggedUserId'));
        }

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


        $this->tokenStorage->setToken(new Token($user, $data['password'],'secured_area'));
        $this->session->set('loggedUserId', $user->getId());

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
        return $this->session->has('loggedUserId');
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

        return $this->userRepository->find($this->session->get('loggedUserId'));
    }

}