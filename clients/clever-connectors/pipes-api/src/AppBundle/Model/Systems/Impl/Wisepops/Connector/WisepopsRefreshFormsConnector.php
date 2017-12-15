<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Wisepops\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Wisepops\WisepopsSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;

/**
 * Class WisepopsRefreshFormsConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Wisepops\Connector
 */
class WisepopsRefreshFormsConnector implements ConnectorInterface
{

    private const  INFO_URL = 'https://app.wisepops.com/api1/wisepops';

    /**
     * @var SystemInstallRepository|ObjectRepository
     */
    private $systemInstallRepository;

    /**
     * @var CurlManagerInterface
     */
    private $curlManager;

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * WisepopsRefreshFormsConnector constructor.
     *
     * @param DocumentManager      $dm
     * @param CurlManagerInterface $curlManager
     */
    public function __construct(DocumentManager $dm, CurlManagerInterface $curlManager)
    {
        $this->dm                      = $dm;
        $this->systemInstallRepository = $dm->getRepository(SystemInstall::class);
        $this->curlManager             = $curlManager;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'wisepops-refresh-forms-connector';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto|void
     * @throws ConnectorException
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        throw new ConnectorException(
            'Wisepops get info has no support for event!',
            ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_EVENT
        );
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ConnectorException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $sys = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
        $res = $this->refreshForms($sys);

        return $dto->setData(json_encode($res));
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return array
     */
    public function refreshForms(SystemInstall $systemInstall): array
    {
        $system = new WisepopsSystem();

        $dto = $system->getRequestDto($systemInstall, 'GET');
        $dto->setUri(new Uri(self::INFO_URL));

        $res   = $this->curlManager->send($dto);
        $forms = json_decode($res->getBody(), TRUE);

        $sForms = [];

        $sett = $systemInstall->getSettings();
        if (array_key_exists(SystemInstall::FORMS, $sett)) {
            $sForms = $sett[SystemInstall::FORMS];

            foreach ($sForms as $form) {
                $this->removeForm($forms, $form[WisepopsSystem::FORM_ID]);
            }
        }

        foreach ($forms as $form) {
            $sForms[] = [
                WisepopsSystem::FORM_ID   => $form['id'],
                WisepopsSystem::FORM_NAME => $form['label'],
                WisepopsSystem::FORM_LIST => NULL,
            ];
        }

        $sett[SystemInstall::FORMS] = $sForms;
        $systemInstall->setSettings($sett);
        $this->dm->flush();

        return $sForms;
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