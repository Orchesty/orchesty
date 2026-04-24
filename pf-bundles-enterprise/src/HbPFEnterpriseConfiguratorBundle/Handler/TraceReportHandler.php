<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\PipesFrameworkEnterprise\TraceReport\Document\TraceReport;
use Hanaboso\PipesFrameworkEnterprise\TraceReport\Repository\TraceReportRepository;
use Hanaboso\UserBundle\Document\User;
use Hanaboso\Utils\Exception\PipesFrameworkException;
use InvalidArgumentException;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Class TraceReportHandler
 *
 * @package Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Handler
 */
final class TraceReportHandler
{

    /**
     * TraceReportHandler constructor.
     *
     * @param DocumentManager $dm
     * @param Security        $security
     */
    public function __construct(
        private readonly DocumentManager $dm,
        private readonly Security $security,
    )
    {
    }

    /**
     * @param int $page
     * @param int $limit
     *
     * @return mixed[]
     * @throws PipesFrameworkException
     */
    public function list(int $page = 1, int $limit = 50): array
    {
        $userId = $this->getUserId();
        $result = $this->getRepository()->findByUser($userId, $page, $limit);

        return [
            'items' => array_map(static fn(TraceReport $r): array => $r->toArray(), $result['items']),
            'limit' => $limit,
            'page'  => $page,
            'total' => $result['total'],
        ];
    }

    /**
     * @param mixed[] $data
     *
     * @return mixed[]
     * @throws MongoDBException
     * @throws PipesFrameworkException
     */
    public function create(array $data): array
    {
        $title       = $this->requireString($data, TraceReport::TITLE);
        $contentHtml = $this->requireString($data, TraceReport::CONTENT_HTML);
        $messageId   = isset($data[TraceReport::MESSAGE_ID]) && is_string($data[TraceReport::MESSAGE_ID])
            ? $data[TraceReport::MESSAGE_ID]
            : NULL;

        $report = new TraceReport();
        $report
            ->setUserId($this->getUserId())
            ->setTitle($title)
            ->setContentHtml($contentHtml)
            ->setMessageId($messageId);

        $this->dm->persist($report);
        $this->dm->flush();

        return $report->toArray();
    }

    /**
     * @param string  $id
     * @param mixed[] $data
     *
     * @return mixed[]
     * @throws MongoDBException
     * @throws PipesFrameworkException
     */
    public function update(string $id, array $data): array
    {
        $report = $this->getOwned($id);

        if (isset($data[TraceReport::TITLE]) && is_string($data[TraceReport::TITLE]) && $data[TraceReport::TITLE] !== '') {
            $report->setTitle($data[TraceReport::TITLE]);
        }

        if (isset($data[TraceReport::CONTENT_HTML]) && is_string($data[TraceReport::CONTENT_HTML])) {
            $report->setContentHtml($data[TraceReport::CONTENT_HTML]);
        }

        $this->dm->flush();

        return $report->toArray();
    }

    /**
     * @param string $id
     *
     * @return mixed[]
     * @throws MongoDBException
     * @throws PipesFrameworkException
     */
    public function delete(string $id): array
    {
        $report = $this->getOwned($id);
        $data   = $report->toArray();

        $this->dm->remove($report);
        $this->dm->flush();

        return $data;
    }

    /**
     * @return TraceReportRepository
     */
    private function getRepository(): TraceReportRepository
    {
        /** @var TraceReportRepository $repository */
        $repository = $this->dm->getRepository(TraceReport::class);

        return $repository;
    }

    /**
     * @return string
     * @throws PipesFrameworkException
     */
    private function getUserId(): string
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new PipesFrameworkException('Authenticated user is required.');
        }

        return $user->getId();
    }

    /**
     * @param string $id
     *
     * @return TraceReport
     * @throws PipesFrameworkException
     */
    private function getOwned(string $id): TraceReport
    {
        $report = $this->getRepository()->find($id);
        if (!$report instanceof TraceReport) {
            throw new InvalidArgumentException(sprintf('Trace report [%s] not found.', $id));
        }

        if ($report->getUserId() !== $this->getUserId()) {
            throw new InvalidArgumentException(sprintf('Trace report [%s] not found.', $id));
        }

        return $report;
    }

    /**
     * @param mixed[] $data
     * @param string  $key
     *
     * @return string
     * @throws PipesFrameworkException
     */
    private function requireString(array $data, string $key): string
    {
        if (!isset($data[$key]) || !is_string($data[$key]) || $data[$key] === '') {
            throw new PipesFrameworkException(sprintf("Missing or invalid '%s' parameter.", $key));
        }

        return $data[$key];
    }

}
