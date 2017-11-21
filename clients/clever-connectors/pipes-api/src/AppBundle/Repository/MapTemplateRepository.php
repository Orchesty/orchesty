<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Repository;

use CleverConnectors\AppBundle\Document\MapTemplate;
use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\DataLayoutActionEnum;
use Doctrine\ODM\MongoDB\DocumentRepository;

/**
 * Class MapTemplateRepository
 *
 * @package CleverConnectors\AppBundle\Repository
 */
class MapTemplateRepository extends DocumentRepository
{

    /**
     * @param SystemInstall        $systemInstall
     * @param DataLayoutActionEnum $action
     * @param string               $direction
     *
     * @return MapTemplate|null
     */
    public function findUnique(SystemInstall $systemInstall, DataLayoutActionEnum $action, string $direction): ?MapTemplate
    {
        /** @var MapTemplate|null $result */
        $result = $this->createQueryBuilder()
            ->field('systemInstall')->equals($systemInstall->getId())
            ->field('action')->equals($action->getValue())
            ->field('direction')->equals($direction)
            ->getQuery()->getSingleResult();

        return $result ?? NULL;
    }

}