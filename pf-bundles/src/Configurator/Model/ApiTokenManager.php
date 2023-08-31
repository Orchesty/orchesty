<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Model;

use DateTime;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentNotFoundException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\Persistence\ObjectRepository;
use Exception;
use Hanaboso\CommonsBundle\Database\Locator\DatabaseManagerLocator;
use Hanaboso\MongoDataGrid\GridRequestDtoInterface;
use Hanaboso\PipesFramework\Configurator\Document\ApiToken;
use Hanaboso\PipesFramework\Configurator\Model\Filters\ApiTokenFilter;
use Hanaboso\PipesFramework\Configurator\Repository\ApiTokenRepository;

/**
 * Class ApiTokenManager
 *
 * @package Hanaboso\PipesFramework\Configurator\Model
 */
final class ApiTokenManager
{

    public const CREATED_TOKEN        = 'token';
    public const CREATED_TOKEN_IS_NEW = 'isNew';

    /**
     * @var ObjectRepository<ApiToken>&ApiTokenRepository
     */
    private ApiTokenRepository $repository;

    /**
     * @var DocumentManager
     */
    private DocumentManager $dm;

    /**
     * ApiTokenManager constructor.
     *
     * @param ApiTokenFilter         $apiTokenFilter
     * @param DatabaseManagerLocator $dml
     */
    public function __construct(private readonly ApiTokenFilter $apiTokenFilter, DatabaseManagerLocator $dml)
    {
        /** @var DocumentManager $dm */
        $dm               = $dml->getDm();
        $this->dm         = $dm;
        $this->repository = $dm->getRepository(ApiToken::class);
    }

    /**
     * @param GridRequestDtoInterface $dto
     *
     * @return array<mixed>
     * @throws MongoDBException
     * @throws Exception
     */
    public function getAllBy(GridRequestDtoInterface $dto): array
    {
        return $this->apiTokenFilter->getData($dto)->toArray();
    }

    /**
     * @param string $id
     *
     * @return ApiToken
     * @throws DocumentNotFoundException
     */
    public function getOne(string $id): ApiToken
    {
        /** @var ApiToken|null $apiToken */
        $apiToken = $this->repository->findOneBy([ApiToken::ID => $id]);

        if (!$apiToken) {
            throw new DocumentNotFoundException(sprintf("Document ApiToken with key '%s' not found!", $id));
        }

        return $apiToken;
    }

    /**
     * @param mixed[] $data
     * @param string  $user
     *
     * @return mixed[]
     * @throws Exception
     */
    public function create(array $data, string $user): array
    {
        $key           = $data[ApiToken::KEY] ?? hash('sha256', random_bytes(60));
        $existingToken = $this->repository->findOneBy(['key' => $key]);
        if ($existingToken) {
            return [self::CREATED_TOKEN => $existingToken, self::CREATED_TOKEN_IS_NEW => FALSE];
        }

        $apiToken = new ApiToken();
        $apiToken
            ->setKey($key)
            ->setUser($user)
            ->setExpireAt(!empty($data[ApiToken::EXPIRE_AT]) ? new DateTime($data[ApiToken::EXPIRE_AT]) : NULL)
            ->setScopes($data[ApiToken::SCOPES]);

        $this->dm->persist($apiToken);
        try {
            $this->dm->flush();
        } catch (Exception) {
            $this->create($data, $user);
        }

        return [self::CREATED_TOKEN => $apiToken, self::CREATED_TOKEN_IS_NEW => TRUE];
    }

    /**
     * @param ApiToken $apiToken
     *
     * @return ApiToken
     * @throws MongoDBException
     */
    public function delete(ApiToken $apiToken): ApiToken
    {
        $this->dm->remove($apiToken);
        $this->dm->flush();

        return $apiToken;
    }

}
