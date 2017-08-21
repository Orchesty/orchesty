<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Source;

use Hanaboso\PipesFramework\Commons\Node\BaseNode;

/**
 * Class SourceConnector
 *
 * @package Hanaboso\PipesFramework\Commons\Source
 */
abstract class SourceConnector extends BaseNode
{

    /**
     * @var SourceService
     */
    protected $sourceService;

    /**
     * SourceConnector constructor.
     *
     * @param string        $id
     * @param SourceService $sourceService
     */
    public function __construct(string $id, SourceService $sourceService)
    {
        parent::__construct($id);
        $this->sourceService = $sourceService;
    }

}
