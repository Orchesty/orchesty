<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\User\Model\Token;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManager;
use Hanaboso\PipesFramework\Acl\Enum\ResourceEnum;
use Hanaboso\PipesFramework\HbPFAclBundle\Provider\ResourceProvider;
use Hanaboso\PipesFramework\User\DatabaseManager\UserDatabaseManagerLocator;
use Hanaboso\PipesFramework\User\Document\Token;
use Hanaboso\PipesFramework\User\Entity\TokenInterface;
use Hanaboso\PipesFramework\User\Entity\UserInterface;
use Hanaboso\PipesFramework\User\Enum\UserTypeEnum;
use Hanaboso\PipesFramework\User\Repository\Document\TokenRepository as DocumentTokenRepository;
use Hanaboso\PipesFramework\User\Repository\Entity\TokenRepository as EntityTokenRepository;

/**
 * Class TokenManager
 *
 * @package Hanaboso\PipesFramework\HbPFUserBundle\Manager
 */
class TokenManager
{

    /**
     * @var DocumentManager|EntityManager
     */
    private $dm;

    /**
     * @var ResourceProvider
     */
    private $provider;

    /**
     * TokenManager constructor.
     *
     * @param UserDatabaseManagerLocator $userDml
     * @param ResourceProvider           $provider
     */
    public function __construct(UserDatabaseManagerLocator $userDml, ResourceProvider $provider)
    {
        $this->dm       = $userDml->get();
        $this->provider = $provider;
    }

    /**
     * @param UserInterface $user
     *
     * @return TokenInterface
     */
    public function create(UserInterface $user): TokenInterface
    {
        $class = $this->provider->getResource(ResourceEnum::TOKEN);
        /** @var TokenInterface $token */
        $token = new $class();
        $this->removeExistingTokens($user);
        $user->getType() === UserTypeEnum::USER ? $token->setUser($user) : $token->setTmpUser($user);

        $this->dm->persist($token);
        $this->dm->flush();

        return $token;
    }

    /**
     * @param string $id
     *
     * @return TokenInterface
     * @throws TokenManagerException
     */
    public function validate(string $id): TokenInterface
    {
        /** @var EntityTokenRepository|DocumentTokenRepository $repo */
        $repo = $this->dm->getRepository($this->provider->getResource(ResourceEnum::TOKEN));
        $token = $repo->getFreshToken($id);

        if (!$token) {
            throw new TokenManagerException(
                sprintf('Token \'%s\' not valid.', $id),
                TokenManagerException::TOKEN_NOT_VALID
            );
        }

        return $token;

    }

    /**
     * @param TokenInterface $token
     */
    public function delete(TokenInterface $token): void
    {
        $this->removeExistingTokens($token->getUserOrTmpUser());
        $this->dm->flush();
    }

    /**
     * @param UserInterface $user
     */
    private function removeExistingTokens(UserInterface $user): void
    {
        foreach ($this->dm->getRepository(Token::class)->findBy([$user->getType() => $user]) as $token) {
            $this->dm->remove($token);
        }
    }

}