<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\AuditLog\Enum;

/**
 * Class AuditActionEnum
 *
 * @package Hanaboso\PipesFrameworkEnterprise\AuditLog\Enum
 */
final class AuditActionEnum
{

    public const string CREATED   = 'Created';
    public const string UPDATED   = 'Updated';
    public const string DELETED   = 'Deleted';
    public const string EXECUTED  = 'Executed';
    public const string PUBLISHED = 'Published';

}
