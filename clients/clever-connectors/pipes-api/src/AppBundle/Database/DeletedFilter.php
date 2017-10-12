<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Database;

use CleverConnectors\AppBundle\Enum\DatabaseFilterEnum;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Query\Filter\BsonFilter;

/**
 * Class DeletedFilter
 *
 * @package CleverConnectors\AppBundle\Database
 */
final class DeletedFilter extends BsonFilter
{

    /**
     * @param ClassMetadata $class
     *
     * @return array
     */
    public function addFilterCriteria(ClassMetadata $class): array
    {
        return $class->getReflectionClass()
            ->hasProperty(DatabaseFilterEnum::DELETED) ? ['deleted' => ['$ne' => TRUE]] : [];
    }

}