<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Repository;

use CleverConnectors\AppBundle\Document\MapTemplate;
use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Dto\ActionDto;
use Doctrine\ODM\MongoDB\DocumentRepository;

/**
 * Class MapTemplateRepository
 *
 * @package CleverConnectors\AppBundle\Repository
 */
class MapTemplateRepository extends DocumentRepository
{

    /**
     * @param SystemInstall $systemInstall
     * @param ActionDto     $dto
     *
     * @return MapTemplate|null
     */
    public function findUnique(SystemInstall $systemInstall, ActionDto $dto): ?MapTemplate
    {
        /** @var MapTemplate|null $result */
        $result = $this->createQueryBuilder()
            ->field('systemInstall')->equals($systemInstall->getId())
            ->field('action')->equals($dto->getAction())
            ->field('direction')->equals($dto->getDirection())
            ->getQuery()->getSingleResult();

        return $result ?? NULL;
    }

}