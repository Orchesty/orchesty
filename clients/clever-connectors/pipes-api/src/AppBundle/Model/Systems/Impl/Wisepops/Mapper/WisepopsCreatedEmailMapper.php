<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Wisepops\Mapper;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CM\SubscriberConnector\SubscriberObject\CMSubscriber;
use CleverConnectors\AppBundle\Model\Systems\Impl\Wisepops\WisepopsSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

/**
 * Class WisepopsCreatedEmailMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Wisepops\Mapper
 */
class WisepopsCreatedEmailMapper implements CustomNodeInterface
{

    /**
     * @var SystemInstallRepository|ObjectRepository
     */
    private $systemInstallRepository;

    /**
     * WisepopsCreatedEmailMapper constructor.
     *
     * @param DocumentManager $dm
     */
    public function __construct(DocumentManager $dm)
    {
        $this->systemInstallRepository = $dm->getRepository(SystemInstall::class);
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $data = json_decode($dto->getData(), TRUE);

        if (!array_key_exists('email', $data)) {
            throw new CleverConnectorsException(
                'Missing required email field in data.',
                CleverConnectorsException::MISSING_DATA
            );
        }
        $obj = new CMSubscriber();
        $obj->setEmail($data['email']);

        $sys  = $this->systemInstallRepository->getSystemInstallFromHeaders($dto->getHeaders());
        $sett = $sys->getSettings();

        $forms = array_key_exists(SystemInstall::FORMS, $sett) ? $forms = $sett[SystemInstall::FORMS] : [];
        $id    = $data['wisepop_id'];

        foreach ($forms as $form) {
            if ($form[WisepopsSystem::FORM_ID] === $id) {
                $obj->setLists([$form[WisepopsSystem::FORM_LIST]]);
                break;
            }
        }

        return $dto->setData(json_encode($obj->toArray()));
    }

}