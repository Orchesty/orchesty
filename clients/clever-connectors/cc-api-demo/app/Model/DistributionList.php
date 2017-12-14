<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 12/14/17
 * Time: 9:51 AM
 */

namespace App\Model;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\RequestOptions;
use Tracy\Debugger;

/**
 * Class DistribuitionList
 *
 * @package App\Model
 */
class DistributionList
{

    /**
     * @var string
     */
    private $host;

    /**
     * @var array
     */
    private $secret;

    /**
     * DistributionList constructor.
     *
     * @param string $host
     * @param array  $secret
     */
    public function __construct(string $host, ?array $secret = [])
    {
        $this->host   = $host;
        $this->secret = $secret;
    }

    /**
     * @param string $token
     * @param string $guid
     *
     * @return array
     */
    public function getLists(string $token, string $guid): array
    {
        $uri = new Uri(sprintf(
            '%s/lists',
            $this->host
        ));

        $headers['Accept']       = 'application/json';
        $headers['Content-type'] = 'application/json';
        $headers['X-Api-Key']    = sprintf('%s:%s', $guid, $token);

        $request = new Request('GET', $uri, $headers);

        $opt = [];
        if ($this->secret !== '') {
            $opt = [
                RequestOptions::CERT    => $this->secret['cert'],
                RequestOptions::SSL_KEY => $this->secret['cert'],
                RequestOptions::VERIFY  => $this->secret['ca'],
            ];
        }

        $res = (new Client())->send($request, $opt);

        return json_decode($res->getBody()->getContents(), TRUE);
    }

    /**
     * @param string $token
     * @param string $guid
     *
     * @return array
     */
    public function getListsForSelect(string $token, string $guid): array
    {
        $data = $this->getLists($token, $guid);

        $lists = [];
        foreach ($data as $item) {
            $lists[$item['list_id']] = $item['name'];
        }

        return $lists;
    }

}