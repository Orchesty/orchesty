<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Storage\Mongodb;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\WorkerApi\ClientInterface;
use Hanaboso\Utils\String\Json;

/**
 * Class Repository
 *
 * @template T of DocumentAbstract
 *
 * @package  Hanaboso\PipesPhpSdk\Storage\Mongodb
 */
class Repository
{

    /**
     * Repository constructor.
     *
     * @param ClientInterface $client
     * @param class-string<T> $className
     */
    public function __construct(private readonly ClientInterface $client, private readonly string $className)
    {
    }

    /**
     * @param DocumentAbstract $entity
     *
     * @return $this
     * @throws GuzzleException
     */
    public function insert(DocumentAbstract $entity): self
    {
        return $this->insertMany([$entity]);
    }

    /**
     * @param DocumentAbstract[] $entities
     *
     * @return $this
     * @throws GuzzleException
     */
    public function insertMany(array $entities): self
    {
        foreach ($entities as $entity) {
            $this->beforeSend($entity);
        }
        $path = explode('\\', $this->className);
        $this->client->send(
            sprintf('/document/%s', end($path)),
            array_map(static fn($entity) => $entity->toArray(), $entities),
        );
        foreach ($entities as $entity) {
            $this->afterReceive($entity);
        }

        return $this;
    }

    /**
     * @param DocumentAbstract $entity
     *
     * @return $this
     * @throws GuzzleException
     */
    public function update(DocumentAbstract $entity): self
    {
        return $this->insertMany([$entity]);
    }

    /**
     * @param DocumentAbstract $entity
     *
     * @return $this
     * @throws GuzzleException
     */
    public function remove(DocumentAbstract $entity): self
    {
        if (!$entity->getId()) {
            return $this;
        }
        $this->removeMany(new Filter([$entity->getId()]));

        return $this;
    }

    /**
     * @param Filter $filter
     *
     * @return $this
     * @throws GuzzleException
     */
    public function removeMany(Filter $filter): self
    {
        $path = explode('\\', $this->className);
        $this->client->send(
            sprintf('/document/%s%s', end($path), $this->createQuery($filter)),
            NULL,
            CurlManager::METHOD_DELETE,
        );

        return $this;
    }

    /**
     * @param Filter|null $filter
     * @param Sorter|null $sorter
     * @param Paging|null $paging
     *
     * @return DocumentAbstract|null
     * @throws GuzzleException
     */
    public function findOne(?Filter $filter = NULL, ?Sorter $sorter = NULL, ?Paging $paging = NULL): ?DocumentAbstract
    {
        $result = $this->findMany($filter, $sorter, $paging);

        return !empty($result) ? $result[0] : NULL;
    }

    /**
     * @param string $id
     *
     * @return T|null
     * @throws GuzzleException
     */
    public function findById(string $id): ?DocumentAbstract
    {
        $result = $this->findMany(new Filter([$id]));

        return !empty($result) ? $result[0] : NULL;
    }

    /**
     * @param Filter|NULL $filter
     * @param Sorter|NULL $sorter
     * @param Paging|NULL $paging
     *
     * @return T[] | array
     * @throws GuzzleException
     * @throws Exception
     */
    public function findMany(?Filter $filter = NULL, ?Sorter $sorter = NULL, ?Paging $paging = NULL): array
    {
        $path = explode('\\', $this->className);
        $uri  = sprintf('/document/%s%s', end($path), $this->createQuery($filter, $sorter, $paging));

        // TODO chybi cache
        $result = $this->client->send($uri, NULL, CurlManager::METHOD_GET);

        $body = $result->getBody()->getContents() ?: '{}';
        $body = Json::decode($body);
        if (empty($body)) {
            return [];
        }

        if ($result->getStatusCode() !== 200) {
            throw new Exception($body['message']);
        }

        if ($this->isAssoc($body)) {
            $body = [$body];
        }

        $response = [];
        foreach ($body as $document) {
            $response[] = new $this->className($document);
        }
        foreach ($response as $entity) {
            $this->afterReceive($entity);
        }

        return $response;
    }

    /**
     * @param string[] $ids
     *
     * @return T[]
     * @throws GuzzleException
     */
    public function findManyByIds(array $ids): array
    {
        return $this->findMany(new Filter($ids));
    }

    /**
     * @param DocumentAbstract $entity
     *
     * @return void
     */
    protected function beforeSend(DocumentAbstract $entity): void
    {
        $entity;
    }

    /**
     * @param DocumentAbstract $entity
     *
     * @return void
     */
    protected function afterReceive(DocumentAbstract $entity): void
    {
        $entity;
    }

    /**
     * @param Filter|NULL $filter
     * @param Sorter|NULL $sorter
     * @param Paging|NULL $paging
     *
     * @return string
     */
    private function createQuery(?Filter $filter = NULL, ?Sorter $sorter = NULL, ?Paging $paging = NULL): string
    {
        $queryParams = [];

        if ($filter)
            $queryParams[] = sprintf('filter=%s', $filter->toJson());
        if ($sorter)
            $queryParams[] = sprintf('sorter=%s', $sorter->toJson());
        if ($paging)
            $queryParams[] = sprintf('paging=%s', $paging->toJson());

        return $queryParams ? sprintf('?%s', implode('&', $queryParams)) : '';
    }

    /**
     * @param mixed[] $arr
     *
     * @return bool
     */
    private function isAssoc(array $arr): bool
    {
        if ($arr === array())

        return FALSE;

        return array_keys($arr) !== range(0, count($arr) - 1);
    }

}
