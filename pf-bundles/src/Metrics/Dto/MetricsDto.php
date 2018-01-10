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
        if (!empty($min) || $min === 0) {
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
        if (!empty($max || $max === 0)) {
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
     * @param mixed $count
     * @param mixed $sum
     *
     * @return MetricsDto
     */
    public function setTotal($count, $sum): MetricsDto
    {
        if (!empty($count) && !empty($sum)) {
            $this->total = (string) (($sum / $count) * 100);
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