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
        $routes->add('retrieve', '/{name}')->controller([$this, 'retrieve'])->methods(['GET']);
        $routes->add('create', '/')->controller([$this, 'create'])->methods(['POST']);
    }

    public function retrieve(string $name): Response
    {
        /** @var System $ickle */
        $ickle = $this->getContainer()->get('ickle');

        try {
            $shortLink = $ickle->query(new ReferenceQuery($name));
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
        /** @var System $ickle */
        $ickle = $this->getContainer()->get('ickle');

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

        try {
            $ickle->handle(new CreateReferenceCommand(
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
