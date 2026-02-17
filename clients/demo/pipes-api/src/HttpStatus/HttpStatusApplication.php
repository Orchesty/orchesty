<?php declare(strict_types=1);

namespace Demo\HttpStatus;

use Hanaboso\CommonsBundle\Process\ProcessDtoAbstract;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Model\Form\FormStack;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationAbstract;

/**
 * Class HttpStatusApplication
 *
 * @package Demo\HttpStatus
 */
final class HttpStatusApplication extends BasicApplicationAbstract
{

    public const string NAME = 'http-status-php';

    /**
     * @return string
     */
    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @return string
     */
    public function getPublicName(): string
    {
        return 'HTTP Status (PHP)';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Easily check status codes, response headers, and redirect chains.';
    }

    /**
     * @return string
     */
    public function getLogo(): string
    {
        return 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iODQ2IiBoZWlnaHQ9Ijg0NiIgdmlld0JveD0iMCAwIDg0NiA4NDYiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxjaXJjbGUgY3g9IjQyMyIgY3k9IjQyMyIgcj0iNDIzIiBmaWxsPSIjMDA5OUZGIi8+CjxwYXRoIGZpbGwtcnVsZT0iZXZlbm9kZCIgY2xpcC1ydWxlPSJldmVub2RkIiBkPSJNNDQ3LjY4NCAxNzAuOTU4QzQ0MS41MTkgMTY4LjUyMyA0MzIuNTU2IDE3MC45NDQgNDE0LjYyOCAxNzUuNzg1TDIzOC43MTMgMjIzLjI5MkMyMjAuODkxIDIyOC4xMDUgMjExLjk3OSAyMzAuNTExIDIwNy44ODIgMjM1LjY5NUMyMDQuNDYxIDI0MC4wMjQgMjAyLjk4IDI0NS41NzMgMjAzLjc4OSAyNTEuMDNDMjA0Ljc1OCAyNTcuNTY3IDIxMS4yODUgMjY0LjA5MyAyMjQuMzM5IDI3Ny4xNDdMMjUzLjIyNCAzMDYuMDMyTDE0Ny4xMDEgNDEyLjE1NUMxNDEuNjMzIDQxNy42MjIgMTQxLjYzMyA0MjYuNDg3IDE0Ny4xMDEgNDMxLjk1NEwxOTcuOTU3IDQ4Mi44MTFDMjAzLjQyNSA0ODguMjc4IDIxMi4yODkgNDg4LjI3OCAyMTcuNzU2IDQ4Mi44MTFMMzIzLjg4IDM3Ni42ODhMMzUyLjc0OCA0MDUuNTU2QzM2NS44MDEgNDE4LjYxIDM3Mi4zMjggNDI1LjEzNiAzNzguODY1IDQyNi4xMDZDMzg0LjMyMiA0MjYuOTE1IDM4OS44NzEgNDI1LjQzNCAzOTQuMiA0MjIuMDEzQzM5OS4zODQgNDE3LjkxNSA0MDEuNzkgNDA5LjAwNCA0MDYuNjAzIDM5MS4xODJMNDU0LjEwOSAyMTUuMjY3TDQ1NC4xMSAyMTUuMjY2QzQ1OC45NTEgMTk3LjMzOSA0NjEuMzcyIDE4OC4zNzUgNDU4LjkzNiAxODIuMjExQzQ1Ni45MDQgMTc3LjA2NSA0NTIuODMgMTcyLjk5MSA0NDcuNjg0IDE3MC45NThaIiBmaWxsPSJ3aGl0ZSIvPgo8cGF0aCBmaWxsLXJ1bGU9ImV2ZW5vZGQiIGNsaXAtcnVsZT0iZXZlbm9kZCIgZD0iTTM4Ny4wNDggNjYzLjc4OUMzODQuNjEzIDY1Ny42MjUgMzg3LjAzNCA2NDguNjYxIDM5MS44NzUgNjMwLjczNEw0MzkuMzgyIDQ1NC44MThDNDQ0LjE5NSA0MzYuOTk2IDQ0Ni42MDEgNDI4LjA4NSA0NTEuNzg1IDQyMy45ODhDNDU2LjExMyA0MjAuNTY3IDQ2MS42NjMgNDE5LjA4NSA0NjcuMTIgNDE5Ljg5NUM0NzMuNjU2IDQyMC44NjQgNDgwLjE4MyA0MjcuMzkxIDQ5My4yMzcgNDQwLjQ0NEw1MjIuMTIyIDQ2OS4zMjlMNjI4LjI0MSAzNjMuMjFDNjMzLjcwOCAzNTcuNzQzIDY0Mi41NzMgMzU3Ljc0MyA2NDguMDQgMzYzLjIxTDY5OC44OTcgNDE0LjA2N0M3MDQuMzY0IDQxOS41MzQgNzA0LjM2NCA0MjguMzk5IDY5OC44OTcgNDMzLjg2Nkw1OTIuNzc4IDUzOS45ODVMNjIxLjY0NiA1NjguODUzQzYzNC43IDU4MS45MDcgNjQxLjIyNiA1ODguNDM0IDY0Mi4xOTYgNTk0Ljk3QzY0My4wMDUgNjAwLjQyNyA2NDEuNTI0IDYwNS45NzcgNjM4LjEwMyA2MTAuMzA1QzYzNC4wMDUgNjE1LjQ4OSA2MjUuMDk0IDYxNy44OTYgNjA3LjI3MiA2MjIuNzA5TDQzMS4zNTcgNjcwLjIxNUw0MzEuMzU2IDY3MC4yMTVDNDEzLjQyOSA2NzUuMDU2IDQwNC40NjUgNjc3LjQ3NyAzOTguMzAxIDY3NS4wNDJDMzkzLjE1NSA2NzMuMDA5IDM4OS4wODEgNjY4LjkzNSAzODcuMDQ4IDY2My43ODlaIiBmaWxsPSJ3aGl0ZSIvPgo8L3N2Zz4K';
    }

    /**
     * @return FormStack
     */
    public function getFormStack(): FormStack
    {
        return new FormStack();
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return bool
     */
    public function isAuthorized(ApplicationInstall $applicationInstall): bool
    {
        $applicationInstall;

        return TRUE;
    }

    /**
     * @param ProcessDtoAbstract $dto
     * @param ApplicationInstall $applicationInstall
     * @param string             $method
     * @param string|null        $url
     * @param string|null        $data
     *
     * @return RequestDto
     */
    public function getRequestDto(
        ProcessDtoAbstract $dto,
        ApplicationInstall $applicationInstall,
        string $method,
        ?string $url = NULL,
        ?string $data = NULL,
    ): RequestDto
    {
        $applicationInstall;
        $method;
        $data;

        return new RequestDto(
            $this->getUri(sprintf('https://mock.httpstatus.io/%s', $url)),
            CurlManager::METHOD_GET,
            $dto,
        );
    }

}
