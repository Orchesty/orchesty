<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\User\Manager;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Exception;
use Hanaboso\MongoDataGrid\GridHandlerTrait;
use Hanaboso\MongoDataGrid\GridRequestDto;
use Hanaboso\PipesFramework\User\Filter\TmpUserDocumentFilter;
use Hanaboso\PipesFramework\User\Filter\UserDocumentFilter;
use Hanaboso\UserBundle\Document\TmpUser;
use Hanaboso\UserBundle\Document\User;
use Hanaboso\UserBundle\Model\User\UserManagerException;
use Hanaboso\Utils\Exception\PipesFrameworkException;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

/**
 * Class UserManager
 *
 * @package Hanaboso\PipesFramework\User\Manager
 */
final class UserManager
{

    use GridHandlerTrait;

    /**
     * UserManager constructor.
     *
     * @param UserDocumentFilter             $userFilter
     * @param TmpUserDocumentFilter          $tmpUserFilter
     * @param DocumentManager                $dm
     * @param PasswordHasherFactoryInterface $encoderFactory
     */
    public function __construct(
        private UserDocumentFilter $userFilter,
        private TmpUserDocumentFilter $tmpUserFilter,
        private DocumentManager $dm,
        private PasswordHasherFactoryInterface $encoderFactory,
    )
    {
    }

    /**
     * @param GridRequestDto $dto
     *
     * @return mixed[]
     * @throws Exception
     * @throws MongoDBException
     */
    public function getArrayOfUsers(GridRequestDto $dto): array
    {
        return $this->getGridResponse($dto, $this->userFilter->getData($dto)->toArray());
    }

    /**
     * @param GridRequestDto $dto
     *
     * @return mixed[]
     * @throws Exception
     * @throws MongoDBException
     */
    public function getArrayOfInvitedUsers(GridRequestDto $dto): array
    {
        return $this->getGridResponse($dto, $this->tmpUserFilter->getData($dto)->toArray());
    }

    /**
     * @param string $id
     *
     * @return TmpUser
     * @throws UserManagerException
     */
    public function getInvitedUser(string $id): TmpUser
    {
        /** @var TmpUser|null $tmpUser */
        $tmpUser = $this->dm->getRepository(TmpUser::class)->findOneBy(['id' => $id]);

        if (!$tmpUser) {
            throw new UserManagerException(
                sprintf('Invited user with id [%s] not found.', $id),
                UserManagerException::USER_NOT_EXISTS,
            );
        }

        return $tmpUser;
    }

    /**
     * @param string $id
     *
     * @throws MongoDBException
     * @throws UserManagerException
     */
    public function deleteInvitedUser(string $id): void
    {
        $tmpUser = $this->getInvitedUser($id);

        $token = $tmpUser->getToken();
        if ($token) {
            $this->dm->remove($token);
        }

        $this->dm->remove($tmpUser);
        $this->dm->flush();
    }

    /**
     * @return bool
     */
    public function hasUser(): bool
    {
        return $this->dm->getRepository(User::class)->findOneBy([]) !== NULL;
    }

    /**
     * @param string $email
     * @param string $password
     *
     * @return User
     * @throws MongoDBException
     * @throws PipesFrameworkException
     */
    public function setupUser(string $email, string $password): User
    {
        if ($this->hasUser()) {
            throw new PipesFrameworkException('Setup already completed.');
        }

        $user = new User();
        $user->setEmail($email);
        $user->setPassword($this->encoderFactory->getPasswordHasher(User::class)->hash($password));
        $this->dm->persist($user);
        $this->dm->flush();

        return $user;
    }

}
