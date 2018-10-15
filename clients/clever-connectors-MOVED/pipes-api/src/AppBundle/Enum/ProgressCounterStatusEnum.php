<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 3.10.17
 * Time: 15:59
 */

namespace CleverConnectors\AppBundle\Enum;

use Hanaboso\CommonsBundle\Enum\EnumAbstract;

/**
 * Class ProgressCounterStatusEnum
 *
 * @package Hanaboso\CommonsBundle\Enum
 */
class ProgressCounterStatusEnum extends EnumAbstract
{

    public const IN_PROGRESS = 'in_progress';
    public const SUCCESS     = 'success';
    public const FAILED      = 'failed';

    /**
     * @var array
     */
    protected static $choices = [
        self::IN_PROGRESS => 'in_progress',
        self::SUCCESS     => 'success',
        self::FAILED      => 'failed',
    ];

    /**
     * @param bool $state
     *
     * @return string
     */
    public static function from(bool $state): string
    {
        if ($state) {
            return ProgressCounterStatusEnum::SUCCESS;
        }

        return ProgressCounterStatusEnum::FAILED;
    }

}
