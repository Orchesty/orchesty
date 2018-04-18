<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Repository;

use CleverConnectors\AppBundle\Document\AudienceMirror;
use Doctrine\ODM\MongoDB\DocumentRepository;

/**
 * Class AudienceMirrorRepository
 *
 * @package CleverConnectors\AppBundle\Repository
 */
class AudienceMirrorRepository extends DocumentRepository
{

    /**
     * @param string $audienceId
     *
     * @return AudienceMirror|null
     */
    public function getByAudience(string $audienceId): ?AudienceMirror
    {
        /** @var AudienceMirror $mirr */
        $mirr = $this->findOneBy(['audienceId' => $audienceId]);

        return $mirr;
    }

}