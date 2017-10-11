<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: stanislav.kundrat
 * Date: 10/11/17
 * Time: 2:22 PM
 */

namespace CleverConnectors\AppBundle\Utils\Dto;

use DateTime;

/**
 * Class Times
 *
 * @package CleverConnectors\AppBundle\Utils\Dto
 */
class Times
{

    /**
     * @var DateTime|null
     */
    private $start;

    /**
     * @var DateTime
     */
    private $end;

    /**
     * Times constructor.
     *
     * @param DateTime|null $start
     * @param DateTime      $end
     */
    public function __construct(?DateTime $start = NULL, DateTime $end)
    {
        $this->start = $start;
        $this->end   = $end;
    }

    /**
     * @return DateTime|null
     */
    public function getStart(): ?DateTime
    {
        return $this->start;
    }

    /**
     * @return DateTime
     */
    public function getEnd(): DateTime
    {
        return $this->end;
    }

}