<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Model;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Configurator\Document\TopologyProgress;

/**
 * Class ProgressManager
 *
 * @package Hanaboso\PipesFramework\Configurator\Model
 */
final class ProgressManager
{

    /**
     * @var DocumentManager
     */
    private DocumentManager $dm;

    /**
     * ProgressManager constructor.
     *
     * @param DocumentManager $dm
     */
    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
    }

    /**
     * @param string $topologyId
     *
     * @return array<mixed>
     */
    public function getProgress(string $topologyId): array
    {
        $res  = [];
        $docs = $this->dm->getRepository(TopologyProgress::class)
            ->findBy(['topologyId' => $topologyId],['created' =>'desc'], 20);

        foreach ($docs ?? [] as $doc) {
            $res[] = $doc->toArray();
        }

        return $res;
    }

}
