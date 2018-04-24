<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Wisepops\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Wisepops\WisepopsSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Traits\LoggerTrait;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\NullLogger;

/**
 * Class WisepopsRefreshFormsConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Wisepops\Connector
 */
class WisepopsRefreshFormsConnector implements ConnectorInterface, LoggerAwareInterface
{

    use LoggerTrait;

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
        $this->logger                  = new NullLogger();
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
     * @return ProcessDto
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
     * @throws SystemException
     * @throws CurlException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $sys = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
        $res = $this->refreshForms($sys, $dto);

        return $dto->setData(json_encode($res));
    }

    /**
     * @param SystemInstall   $systemInstall
     * @param ProcessDto|NULL $processDto
     *
     * @return array
     * @throws SystemException
     * @throws CurlException
     */
    public function refreshForms(SystemInstall $systemInstall, ?ProcessDto $processDto = NULL): array
    {
        $system = new WisepopsSystem();

        $dto = $system->getRequestDto($systemInstall, 'GET');
        $dto->setUri(new Uri(self::INFO_URL));

        try {
            $res = $this->curlManager->send($dto);

            $forms = json_decode($res->getBody(), TRUE);

            $sForms = [];

            $sett = $systemInstall->getSettings();
            if (array_key_exists(SystemInstall::FORMS, $sett)) {
                $sForms = $sett[SystemInstall::FORMS];

                foreach ($sForms as $index => $form) {
                    if (!$this->removeForm($forms, $form[WisepopsSystem::FORM_ID])) {
                        unset($sForms[$index]);
                    }
                }
            }

            foreach ($forms as $form) {
                $sForms[] = [
                    WisepopsSystem::FORM_ID   => (string) $form['id'],
                    WisepopsSystem::FORM_NAME => $form['label'],
                    WisepopsSystem::FORM_LIST => NULL,
                ];
            }

            $sForms = array_values($sForms);

            $sett[SystemInstall::FORMS] = $sForms;
            $systemInstall->setSettings($sett);
            $this->dm->flush();

            return $sForms;
        } catch (CurlException $e) {
            if (!$processDto) {
                $processDto = new ProcessDto();
            }

            $this->connectorError($e, $system, $systemInstall, $processDto);
        }

        return [];
    }

    /**
     * @param array      $array
     * @param int|string $id
     *
     * @return bool
     */
    private function removeForm(array &$array, $id): bool
    {
        foreach ($array as $index => $item) {
            if ($id == $item['id']) {
                unset($array[$index]);

                return TRUE;
            }
        }

        return FALSE;
    }

}