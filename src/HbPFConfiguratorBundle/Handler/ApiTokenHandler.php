<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentNotFoundException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Exception;
use Hanaboso\MongoDataGrid\GridHandlerTrait;
use Hanaboso\MongoDataGrid\GridRequestDtoInterface;
use Hanaboso\PipesFramework\Configurator\Document\ApiToken;
use Hanaboso\PipesFramework\Configurator\Enum\ApiTokenScopesEnum;
use Hanaboso\PipesFramework\Configurator\Model\ApiTokenManager;
use Hanaboso\Utils\Validations\Validations;

/**
 * Class ApiTokenHandler
 *
 * @package Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler
 */
final class ApiTokenHandler
{

    use GridHandlerTrait;

    /**
     * ApiTokenHandler constructor.
     *
     * @param ApiTokenManager $manager
     */
    public function __construct(private readonly ApiTokenManager $manager)
    {
    }

    /**
     * @param GridRequestDtoInterface $dto
     *
     * @return mixed[]
     * @throws MongoDBException
     */
    public function getAllBy(GridRequestDtoInterface $dto): array
    {
        $items = $this->manager->getAllBy($dto);

        return $this->getGridResponse($dto, $items);
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
        Validations::checkParams([ApiToken::SCOPES], $data);
        foreach ($data[ApiToken::SCOPES] as $scopes) {
            ApiTokenScopesEnum::isValid($scopes);
        }

        return $this->manager->create($data, $user)[ApiTokenManager::CREATED_TOKEN]->toArray();
    }

    /**
     * @param string $id
     *
     * @return mixed[]
     * @throws DocumentNotFoundException
     * @throws MongoDBException
     */
    public function delete(string $id): array
    {
        return $this->manager->delete($this->get($id))->toArray();
    }

    /**
     * @param string $id
     *
     * @return ApiToken
     * @throws DocumentNotFoundException
     */
    private function get(string $id): ApiToken
    {
        return $this->manager->getOne($id);
    }

}
