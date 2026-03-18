<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\User\Manager;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Exception;
use Hanaboso\MongoDataGrid\GridHandlerTrait;
use Hanaboso\MongoDataGrid\GridRequestDto;
use Hanaboso\PipesFramework\User\Filter\UserDocumentFilter;
use Hanaboso\UserBundle\Document\User;
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
     * @param DocumentManager                $dm
     * @param PasswordHasherFactoryInterface $encoderFactory
     */
    public function __construct(
        private UserDocumentFilter $userFilter,
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
