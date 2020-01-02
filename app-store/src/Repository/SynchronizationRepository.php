<?php declare(strict_types=1);

namespace Hanaboso\HbPFAppStore\Repository;

use Doctrine\ODM\MongoDB\DocumentNotFoundException;
use Doctrine\ODM\MongoDB\Iterator\Iterator;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ODM\MongoDB\Query\Builder;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Hanaboso\HbPFAppStore\Document\Synchronization;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use LogicException;

/**
 * Class SynchronizationRepository
 *
 * @package         Hanaboso\HbPFAppStore\Repository
 *
 * @phpstan-extends DocumentRepository<Synchronization>
 */
final class SynchronizationRepository extends DocumentRepository
{

    private const CONSTANTS = [
        Synchronization::KEY,
        Synchronization::USER,
        Synchronization::STATUS,
        Synchronization::INTERNAL_ID,
        Synchronization::EXTERNAL_ID,
        Synchronization::INTERNAL_HASH,
        Synchronization::EXTERNAL_HASH,
        Synchronization::DATA,
    ];

    /**
     * @param ApplicationInstall $applicationInstall
     * @param mixed[]            $filter
     *
     * @return Synchronization|null
     * @throws MongoDBException
     */
    public function get(ApplicationInstall $applicationInstall, array $filter = []): ?Synchronization
    {
        /** @var Iterator<Synchronization> $iterator */
        $iterator = $this->processFilter($this->createBuilder($applicationInstall), $filter)
            ->getQuery()
            ->execute();

        $synchronization = $iterator->toArray();

        return reset($synchronization) ?: NULL;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param mixed[]            $filter
     * @param mixed[]            $data
     *
     * @return Synchronization
     * @throws DocumentNotFoundException
     * @throws MongoDBException
     */
    public function update(
        ApplicationInstall $applicationInstall,
        array $filter = [],
        array $data = []
    ): Synchronization
    {
        /** @var Synchronization|null $synchronization */
        $synchronization = $this->processContent(
            $this->processFilter(
                $this->createUpdateBuilder($applicationInstall),
                $filter
            ),
            $data
        )->getQuery()->execute();

        if (!$synchronization) {
            throw new DocumentNotFoundException(
                sprintf(
                    "Synchronization document with key '%s' and user '%s' not found!",
                    $applicationInstall->getKey(),
                    $applicationInstall->getUser()
                )
            );
        }

        return $synchronization;
    }

    /**
     * @param Builder $builder
     * @param mixed[] $data
     *
     * @return Builder
     */
    private function processFilter(Builder $builder, array $data): Builder
    {
        foreach ($data as $key => $value) {
            $builder->field($this->processField($key))->equals($value);
        }

        return $builder;
    }

    /**
     * @param Builder $builder
     * @param mixed[] $data
     *
     * @return Builder
     */
    private function processContent(Builder $builder, array $data): Builder
    {
        foreach ($data as $key => $value) {
            $builder->field($this->processField($key))->set($value);
        }

        return $builder;
    }

    /**
     * @param string $field
     *
     * @return string
     */
    private function processField(string $field): string
    {
        if (in_array($field, self::CONSTANTS, TRUE)) {
            return $field;
        }

        foreach (self::CONSTANTS as $constant) {
            if (strpos($field, sprintf('%s.', $constant)) === 0) {
                return $field;
            }
        }

        throw new LogicException(sprintf('Constant %s::%s not found!', Synchronization::class, $field));
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return Builder
     */
    private function createBuilder(ApplicationInstall $applicationInstall): Builder
    {
        return $this->createQueryBuilder()
            ->field(Synchronization::KEY)
            ->equals($applicationInstall->getKey())
            ->field(Synchronization::USER)
            ->equals($applicationInstall->getUser());
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return Builder
     */
    private function createUpdateBuilder(ApplicationInstall $applicationInstall): Builder
    {
        return $this->createBuilder($applicationInstall)
            ->findAndUpdate()
            ->returnNew();
    }

}