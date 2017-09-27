<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Document\Traits;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Trait IdTrait
 *
 * @package CleverConnectors\AppBundle\Document\Traits
 */
trait IdTrait
{

    /**
     * @var string
     *
     * @ODM\Id()
     */
    protected $id;

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

}