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
     * @param string $type
     *
     * @return AudienceMirror|null
     */
    public function getByAudience(string $audienceId, string $type): ?AudienceMirror
    {
        /** @var AudienceMirror $mirr */
        $mirr = $this->findOneBy([
            'audienceId' => $audienceId,
            'type'       => $type,
        ]);

        return $mirr;
    }

}