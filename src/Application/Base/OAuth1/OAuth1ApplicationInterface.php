<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Application\Base\OAuth1;

use Hanaboso\PipesFramework\Application\Base\ApplicationInterface;
use Hanaboso\PipesFramework\Application\Document\ApplicationInstall;

/**
 * Interface OAuth1ApplicationInterface
 *
 * @package Hanaboso\PipesFramework\Application\Base\OAuth1
 */
interface OAuth1ApplicationInterface extends ApplicationInterface
{

    public const  OAUTH           = 'oauth';
    public const  CONSUMER_KEY    = 'consumer_key';
    public const  CONSUMER_SECRET = 'consumer_secret';

    /**
     * @param ApplicationInstall $applicationInstall
     */
    public function authorize(ApplicationInstall $applicationInstall): void;

}