<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 26.10.17
 * Time: 8:07
 */

namespace CleverConnectors\AppBundle\Traits;

use LogicException;

/**
 * Trait StaticTrait
 *
 * @package CleverConnectors\AppBundle\Traits
 */
trait StaticTrait
{

    /**
     * StaticTrait constructor.
     */
    public function __construct()
    {
        throw new LogicException(sprintf('Class "%s" is static!', get_class($this)));
    }

}