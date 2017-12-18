<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: michal.bartl
 * Date: 12/5/17
 * Time: 3:52 PM
 */

namespace CleverConnectors\AppBundle\Model\Systems\Impl\FacebookLeads\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\FacebookLeads\FacebookLeadsSystem;
use CleverConnectors\AppBundle\Model\Systems\SystemInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;

/**
 * Class FacebookGetLeadformConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\FacebookLeads\Connector
 */
class FacebookGetLeadformConnector implements ConnectorInterface
{

    /**
     * @var CurlManager
     */
    private $curlManager;
    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * FacebookGetLeadformConnector constructor.
     *
     * @param CurlManager     $curlManager
     * @param DocumentManager $dm
     */
    public function __construct(CurlManager $curlManager, DocumentManager $dm)
    {

        $this->curlManager = $curlManager;
        $this->dm = $dm;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'facebook-get-leadform-connector';

    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto|void
     * @throws SystemException
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        throw new SystemException('Facebook Leads has not implemented "processEvent" function.');
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto|void
     * @throws SystemException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        throw new SystemException('Facebook Leads  has not implemented "processAction" function.');
    }

    /**
     * @param SystemInterface $system
     * @param SystemInstall   $systemInstall
     * @param string          $pageId
     *
     * @return array
     */
    public function getLeadForms(SystemInterface $system, SystemInstall $systemInstall, string $pageId): array
    {
        $settings = $systemInstall->getSettings();
        $requestDto = $system->getRequestDto($systemInstall, CurlManager::METHOD_GET);
        $url        = new Uri(
            $requestDto->getUri(TRUE) . '/' . $pageId . '/leadgen_forms?limit=1000&fields=id%2Cname&access_token=' . urlencode($settings[OAuth2Provider::ACCESS_TOKEN])
        );
        $response   = $this->curlManager->send(RequestDto::from($requestDto, $url));
        if ($response->getStatusCode() >= 200 && $response->getStatusCode()) {
            $data = json_decode($response->getBody(), TRUE);

            $sForms = [];

            if (array_key_exists(SystemInstall::FORMS, $settings)) {
                $sForms = $settings[SystemInstall::FORMS];

                foreach ($sForms as $form) {
                    $this->removeForm($form, $form[FacebookLeadsSystem::FORM_ID]);
                }
            }

            foreach ($data['data'] as $form) {
                $sForms[] = [
                    FacebookLeadsSystem::FORM_ID   => $form['id'],
                    FacebookLeadsSystem::FORM_NAME => $form['name'],
                    FacebookLeadsSystem::FORM_LIST => NULL,
                ];
            }

            $sett[SystemInstall::FORMS] = $sForms;
            $systemInstall->setSettings($sett);
            $this->dm->flush();

            return $sForms;

        } else {
            return []; // TODO Vyhodit exception
        }
    }

    /**
     * @param array      $array
     * @param int|string $id
     */
    private function removeForm(array &$array, $id): void
    {
        foreach ($array as $index => $item) {
            if ($id === $item['id']) {
                unset($array[$index]);
                break;
            }
        }
    }

}