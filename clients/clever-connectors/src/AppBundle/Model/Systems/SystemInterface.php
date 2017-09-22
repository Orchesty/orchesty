<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems;

/**
 * Interface SystemInterface
 *
 * @package CleverConnectors\AppBundle\Model\Systems
 */
interface SystemInterface
{

    /**
     * @return string
     */
    public function getType(): string;

    /**
     * @return string
     */
    public function getKey(): string;

    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @return string
     */
    public function getDescription(): string;

    /**
     * @return string
     */
    public function getLogo(): string;

    /**
     * @return array
     */
    public function toArray(): array;

}