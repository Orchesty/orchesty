<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\User\Model\Token;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Hanaboso\PipesFramework\HbPFTableParserBundle\Enum\UserTypeEnum;
use Hanaboso\PipesFramework\User\Document\Token;
use Hanaboso\PipesFramework\User\Document\UserInterface;
use Hanaboso\PipesFramework\User\Repository\TokenRepository;

/**
 * Class TokenManager
 *
 * @package Hanaboso\PipesFramework\HbPFUserBundle\Manager
 */
class TokenManager
{

    /**
     * @var DocumentManager
     */
    private $documentManager;

    /**
     * @var TokenRepository|DocumentRepository
     */
    private $tokenRepository;

    /**
     * TokenManager constructor.
     *
     * @param DocumentManager $documentManager
     */
    public function __construct(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
        $this->tokenRepository = $documentManager->getRepository(Token::class);
    }

    /**
     * @param UserInterface $user
     *
     * @return Token
     */
    public function create(UserInterface $user): Token
    {
        $token = new Token();
        $this->removeExistingTokens($user);
        $user->getType() === UserTypeEnum::USER ? $token->setUser($user) : $token->setTmpUser($user);

        $this->documentManager->persist($token);
        $this->documentManager->flush();

        return $token;
    }

    /**
     * @param string $id
     *
     * @return Token
     * @throws TokenManagerException
     */
    public function validate(string $id): Token
    {
        $token = $this->tokenRepository->getFreshToken($id);

        if (!$token) {
            throw new TokenManagerException(
                sprintf('Token \'%s\' not valid.', $id),
                TokenManagerException::TOKEN_NOT_VALID
            );
        }

        return $token;

    }

    /**
     * @param Token $token
     */
    public function delete(Token $token): void
    {
        $this->removeExistingTokens($token->getUserOrTmpUser());
        $this->documentManager->flush();
    }

    /**
     * @param UserInterface $user
     */
    private function removeExistingTokens(UserInterface $user): void
    {
        foreach ($this->tokenRepository->findBy([$user->getType() => $user]) as $token) {
            $this->documentManager->remove($token);
        }
    }

}