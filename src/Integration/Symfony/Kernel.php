<?php

declare(strict_types=1);

namespace App\Integration\Symfony;

use App\Application\Command\CreateReferenceCommand;
use App\Application\Command\CreateReferenceCommandHandler;
use App\Application\Exception\ApplicationException;
use App\Application\Query\ReferenceQuery;
use App\Application\Query\ReferenceQueryHandler;
use App\Infrastructure\Logger\NoopLogger;
use App\Infrastructure\Persistence\PDO\ReferenceRepository;
use App\Infrastructure\RamseyIdentityProvider;
use App\Infrastructure\ServiceBus\SymfonyCommandBus;
use App\Infrastructure\ServiceBus\SymfonyQueryBus;
use App\Infrastructure\SystemCalendar;
use PDO;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
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

    protected function configureContainer(ContainerConfigurator $c): void
    {
        $c->extension('framework', [
            'secret' => $_ENV['SECRET'],
        ]);
        $c->services()->set('app.logger', NoopLogger::class);
        $c->services()->set('pdo', PDO::class)->args([
            '$dsn' => $_ENV['DATABASE_DNS'],
            '$username' => $_ENV['DATABASE_USERNAME'],
            '$passwd' => $_ENV['DATABASE_PASSWORD'],
        ]);
        $c->services()->set('app.reference_repository', ReferenceRepository::class)->args([
            '$connection' => new Reference('pdo')
        ]);
        $c->services()->set('app.identity_provider', RamseyIdentityProvider::class);
        $c->services()->set('app.calendar', SystemCalendar::class);
        $c->services()->set('app.query_handler.reference', ReferenceQueryHandler::class)->args([
            new Reference('app.reference_repository'),
            new Reference('app.logger')
        ]);
        $c->services()->set('app.query_bus', SymfonyQueryBus::class)->args([
            ['App\Application\Query\ReferenceQuery' => new Reference('app.query_handler.reference')]
        ])->public();
        $c->services()->set('app.command_handler.create_reference', CreateReferenceCommandHandler::class)->args([
            new Reference('app.identity_provider'),
            new Reference('app.calendar'),
            new Reference('app.reference_repository'),
            new Reference('app.logger')
        ]);
        $c->services()->set('app.command_bus', SymfonyCommandBus::class)->args([
            ['App\Application\Command\CreateReferenceCommand' => new Reference('app.command_handler.create_reference')]
        ])->public();
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->add('retrieve', '/{name}')->controller([$this, 'retrieve'])->methods(['GET']);
        $routes->add('create', '/')->controller([$this, 'create'])->methods(['POST']);
    }

    public function retrieve(string $name): Response
    {
        $queryBus = $this->getContainer()->get('app.query_bus');

        try {
            $shortLink = $queryBus->handle(new ReferenceQuery($name));
        } catch (ApplicationException | Throwable $exception) {
            return new JsonResponse(
                ['status' => 'error', 'message' => $exception->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return new RedirectResponse($shortLink->asString());
    }

    public function create(Request $request): JsonResponse
    {
        $form = Forms::createFormFactoryBuilder()
            ->addExtension(new HttpFoundationExtension())
            ->addExtension(new ValidatorExtension(Validation::createValidator()))
            ->getFormFactory()
            ->createBuilder(FormType::class)
            ->add('url', TextType::class, [
                'constraints' => [
                    new NotBlank(),
                    new Length(['min' => 5, 'max' => 255])
                ],
            ])
            ->add('name', TextType::class, [
                'constraints' => [
                    new NotBlank(),
                    new Length(['min' => 5, 'max' => 255]),
                ],
            ])
            ->getForm()
            ->submit(json_decode($request->getContent(), true));

        if (!$form->isValid()) {
            return new JsonResponse(
                ['status' => 'error', 'message' => $this->retrieveErrorsFromForm($form)],
                Response::HTTP_BAD_REQUEST
            );
        }

        $commandBus = $this->getContainer()->get('app.command_bus');

        try {
            $commandBus->handle(new CreateReferenceCommand(
                $form->getData()['url'],
                $form->getData()['name']
            ));
        } catch (ApplicationException | Throwable $exception) {
            return new JsonResponse(
                ['status' => 'error', 'message' => $exception->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return new JsonResponse(['status' => 'success']);
    }

    private function retrieveErrorsFromForm(FormInterface $form): array
    {
        $errors = [];
        foreach ($form->getErrors() as $key => $error) {
            $errors[$key] = $error->getMessage();
        }
        foreach ($form->all() as $child) {
            if (!$child->isValid()) {
                $key = $child->getName();
                $errors[$key] = $this->retrieveErrorsFromForm($child);
            }
        }

        return $errors;
    }
}
