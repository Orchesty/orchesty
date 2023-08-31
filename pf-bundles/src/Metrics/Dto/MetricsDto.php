<?php declare(strict_types=1);

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
    private string $min = '0';

    /**
     * @var string
     */
    private string $max = '0';

    /**
     * @var string
     */
    private string $avg = '0.00';

    /**
     * @var string
     */
    private string $total = '0';

    /**
     * @var string
     */
    private string $errors = '0';

    /**
     * @param mixed $min
     *
     * @return MetricsDto
     */
    public function setMin(mixed $min): self
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
    public function setMax(mixed $max): self
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
    public function setAvg(mixed $count, mixed $sum): self
    {
        if (!empty($count) && !empty($sum)) {
            $this->avg = number_format((float) ($sum / $count), 2, '.', '');
        }

        return $this;
    }

    /**
     * @param mixed $count
     *
     * @return MetricsDto
     */
    public function setTotal(mixed $count): self
    {
        $this->total = (string) $count;

        return $this;
    }

    /**
     * @param mixed $errors
     *
     * @return MetricsDto
     */
    public function setErrors(mixed $errors): self
    {
        $this->errors = (string) $errors;

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

    /**
     * @return string
     */
    public function getErrors(): string
    {
        return $this->errors;
    }

}
