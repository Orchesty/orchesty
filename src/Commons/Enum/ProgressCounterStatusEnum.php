<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 3.10.17
 * Time: 15:59
 */

namespace Hanaboso\PipesFramework\Commons\Enum;

/**
 * Class ProgressCounterStatusEnum
 *
 * @package Hanaboso\PipesFramework\Commons\Enum
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

}
