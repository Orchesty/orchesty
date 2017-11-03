<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 11/3/17
 * Time: 10:24 AM
 */

namespace CleverConnectors\AppBundle\Model\ProgressCounter\Publisher;

/**
 * Interface IProgressStorage
 *
 * @package CleverConnectors\AppBundle\Model\ProgressCounter\Storage
 */
interface IProgressPublisher
{

    /**
     * @param array $data
     */
    public function publish(array $data): void;

}