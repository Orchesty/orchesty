<?php
/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 17.8.17
 * Time: 13:57
 */

namespace Hanaboso\PipesFramework\Authorizations\Provider;

use OAuth;

/**
 * Class OAuth1Provider
 *
 * @package Hanaboso\PipesFramework\Authorizations\Provider
 */
class OAuth1Provider implements ProviderInterface
{

    public function authorize(array $data): void
    {
        $client = $this->createClient($data);

    }

    private function createClient(array $data)
    {


        return new OAuth('', '');
    }
}