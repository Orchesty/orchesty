<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 12.12.17
 * Time: 11:44
 */

namespace Hanaboso\PipesFramework\Metrics\Dto;

/**
 * Class MetricsDto
 *
 * @package Hanaboso\PipesFramework\Metrics\Dto
 */
final class MetricsDto
{

    /**
     * @var string
     */
    private $min = 'n/a';

    /**
     * @var string
     */
    private $max = 'n/a';

    /**
     * @var string
     */
    private $avg = 'n/a';

    /**
     * @var string
     */
    private $total = 'n/a';

    /**
     * @param mixed $min
     *
     * @return MetricsDto
     */
    public function setMin($min): MetricsDto
    {
        if (!empty($min)) {
            $this->min = (string) $min;
        }

        return $this;
    }

    /**
     * @param mixed $max
     *
     * @return MetricsDto
     */
    public function setMax($max): MetricsDto
    {
        if (!empty($max)) {
            $this->max = (string) $max;
        }

        return $this;
    }

    /**
     * @param mixed $count
     * @param mixed $sum
     *
     * @return MetricsDto
     */
    public function setAvg($count, $sum): MetricsDto
    {
        if (!empty($count) && !empty($sum)) {
            $this->avg = (string) ($sum / $count);
        }

        return $this;
    }

    /**
     * @param mixed $total
     *
     * @return MetricsDto
     */
    public function setTotal($total): MetricsDto
    {
        if (!empty($total)) {
            $this->total = (string) $total;
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getMin(): string
    {
        return $this->min;
    }

    /**
     * @return string
     */
    public function getMax(): string
    {
        return $this->max;
    }

    /**
     * @return string
     */
    public function getAvg(): string
    {
        return $this->avg;
    }

    /**
     * @return string
     */
    public function getTotal(): string
    {
        return $this->total;
    }

}