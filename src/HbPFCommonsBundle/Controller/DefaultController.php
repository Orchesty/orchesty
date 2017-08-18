<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFCommonsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class DefaultController
 *
 * @package Hanaboso\PipesFramework\HbPFCommonsBundle\Controller
 */
class DefaultController extends Controller
{

    /**
     * @Route("/api-info")
     *
     * @return Response
     */
    public function indexAction(): Response
    {
        /** @var KernelInterface $kernel */
        $kernel      = $this->get('kernel');
        $application = new Application($kernel);
        $application->setAutoExit(FALSE);

        $input = new ArrayInput([
            'command' => 'debug:router',
        ]);
        // You can use NullOutput() if you don't need the output
        $output = new BufferedOutput();
        $application->run($input, $output);

        // return the output, don't use if you used NullOutput()
        $template = [
            'routes' => $output->fetch(),
        ];

        return $this->render(
            'HbPFCommonsBundle:Default:index.html.twig',
            $template,
            new Response('', 200, ['content-type' => 'text/plain'])
        );
    }

}
