<?php

declare(strict_types=1);

namespace App\UserInterface\Symfony;

use App\Application\Command\CreateReferenceCommand;
use App\Application\Exception\ApplicationException;
use App\Application\Query\ReferenceQuery;
use App\Application\System;
use App\Application\SystemFactory;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Validation;
use Throwable;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function __construct()
    {
        parent::__construct($_ENV['APP_ENV'], $_ENV['APP_ENV'] !== 'prod');
    }

    public function registerBundles(): array
    {
        return [
            new FrameworkBundle(),
        ];
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret' => $_ENV['SECRET'],
        ]);

        $container->services()->set('ickle', System::class)
            ->factory([SystemFactory::class, 'create'])
            ->args(['$database' => $_ENV['DATABASE_URL']])
            ->public();
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->add('redirect', '/{name}')->controller([$this, 'redirect'])->methods(['GET']);
        $routes->add('retrieve', '/shortlink/{name}')->controller([$this, 'retrieve'])->methods(['GET']);
        $routes->add('create', '/shortlink')->controller([$this, 'create'])->methods(['POST']);
    }

    public function redirect(string $name): Response
    {
        /** @var System $ickle */
        $ickle = $this->getContainer()->get('ickle');

        try {
            $shortLink = $ickle->query(new ReferenceQuery($name));
        } catch (ApplicationException $exception) {
            return $this->error($exception->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new RedirectResponse($shortLink->asString());
    }

    public function retrieve(string $name): Response
    {
        /** @var System $ickle */
        $ickle = $this->getContainer()->get('ickle');

        try {
            $shortLink = $ickle->query(new ReferenceQuery($name));
        } catch (ApplicationException $exception) {
            return $this->error($exception->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(['shortlink' => $shortLink->asString()]);
    }

    public function create(Request $request): JsonResponse
    {
        /** @var System $ickle */
        $ickle = $this->getContainer()->get('ickle');

        $data = json_decode($request->getContent(), true);
        $violations = Validation::createValidator()->validate($data, new Collection([
            'url' => [
                new NotBlank(['message' => 'URL should not be blank.']),
                new Url(['message' => 'URL is not valid'])
            ],
            'name' => [
                new NotBlank(['message' => 'Name should not be blank.']),
                new Length([
                    'min' => 5,
                    'max' => 255,
                    'minMessage' => 'Name is too short.',
                    'maxMessage' => 'Name is too long.',
                ]),
            ]
        ]));

        if ($violations->count() > 0) {
            return $this->error($violations->get(0)->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        try {
            $ickle->handle(new CreateReferenceCommand($data['url'], $data['name']));
        } catch (ApplicationException $exception) {
            return $this->error($exception->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(['status' => 'success'], Response::HTTP_CREATED);
    }

    private function error(string $message, int $status): JsonResponse
    {
        return new JsonResponse(['status' => 'error', 'message' => $message], $status);
    }
}
