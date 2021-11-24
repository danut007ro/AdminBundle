<?php

declare(strict_types=1);

namespace DG\AdminBundle\Maker;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AdminMaker extends AbstractMaker
{
    private const ADMIN_FOLDER = 'Admin';

    private string $controllerClassName;

    public static function getCommandName(): string
    {
        return 'make:dg_admin';
    }

    public static function getCommandDescription(): string
    {
        return 'Creates a new DGAdmin Grid';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->addArgument('entity-class', InputArgument::OPTIONAL, sprintf('The class name of the entity to create Admin (e.g. <fg=yellow>%s</>)', Str::asClassName(Str::getRandomTerm())))
            ->addOption('crud', null, InputOption::VALUE_NONE, 'Use this option to add create, update, delete functionality')
            ->addOption('filter', null, InputOption::VALUE_NONE, 'Use this option to add filter Form')
        ;

        $inputConfig->setArgumentAsNonInteractive('entity-class');
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        if (null === $input->getArgument('entity-class')) {
            $argument = $command->getDefinition()->getArgument('entity-class');
            $question = new Question($argument->getDescription());
            $value = $io->askQuestion($question);
            $input->setArgument('entity-class', $value);
        }

        /** @var string $entityClass */
        $entityClass = $input->getArgument('entity-class');
        $defaultControllerClass = Str::asClassName(sprintf('%s Controller', $entityClass));

        $this->controllerClassName = $io->ask(
            sprintf('Choose a name for your controller class (e.g. <fg=yellow>%s</>)', $defaultControllerClass),
            $defaultControllerClass,
        );
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $isCrud = (bool) $input->getOption('crud');
        $hasFilter = (bool) $input->getOption('filter');

        /** @var string $entityClass */
        $entityClass = $input->getArgument('entity-class');
        $entityClassDetails = $generator->createClassNameDetails(
            $entityClass,
            'Entity\\',
        );
        $entityName = $entityClassDetails->getShortName();
        $adminDependenciesNamespace = sprintf('%s\\%s\\', self::ADMIN_FOLDER, $entityName);

        // Create Adapter class.
        $adapterClassNameDetails = $generator->createClassNameDetails(
            'Adapter',
            $adminDependenciesNamespace,
        );
        $generator->generateClass(
            $adapterClassNameDetails->getFullName(),
            __DIR__.'/Resources/Adapter.tpl.php',
            [
                'uses_sort' => [$this, 'usesSort'],
                'entity_full_class_name' => $entityClassDetails->getFullName(),
                'entity_class_name' => $entityName,
            ],
        );

        // Create Configurator class.
        $configuratorClassNameDetails = $generator->createClassNameDetails(
            'Configurator',
            $adminDependenciesNamespace,
        );
        $generator->generateClass(
            $configuratorClassNameDetails->getFullName(),
            __DIR__.'/Resources/Configurator.tpl.php',
            [
                'uses_sort' => [$this, 'usesSort'],
                'entity_full_class_name' => $entityClassDetails->getFullName(),
                'entity_class_name' => $entityName,
                'update_route' => strtolower("{$entityName}_update"),
                'delete_route' => strtolower("{$entityName}_delete"),
                'is_crud' => $isCrud,
                'has_filter' => $hasFilter,
            ],
        );

        // Create Form class.
        if ($isCrud) {
            $formClassNameDetails = $generator->createClassNameDetails(
                'Form',
                $adminDependenciesNamespace,
            );
            $generator->generateClass(
                $formClassNameDetails->getFullName(),
                __DIR__.'/Resources/Form.tpl.php',
                [
                    'uses_sort' => [$this, 'usesSort'],
                    'entity_full_class_name' => $entityClassDetails->getFullName(),
                    'entity_class_name' => $entityName,
                ],
            );
        }

        // Create Filter class.
        if ($hasFilter) {
            $filterClassNameDetails = $generator->createClassNameDetails(
                'Filter',
                $adminDependenciesNamespace,
            );
            $generator->generateClass(
                $filterClassNameDetails->getFullName(),
                __DIR__.'/Resources/Filter.tpl.php',
                [
                    'uses_sort' => [$this, 'usesSort'],
                ],
            );
        }

        // Create Controller class.
        $controllerClassDetails = $generator->createClassNameDetails(
            $input->isInteractive()
                ? $this->controllerClassName
                : Str::asClassName(sprintf('%s Controller', $entityClass)),
            'Controller\\',
            'Controller',
        );

        $crudParameters = false === $isCrud ? [] : [
            'entity_full_class_name' => $entityClassDetails->getFullName(),
            'adapter_full_class_name' => $adapterClassNameDetails->getFullName(),
            'form_full_class_name' => $formClassNameDetails->getFullName(),
            'entity_class_name' => $entityName,
            'admin_namespace' => Str::asClassName(self::ADMIN_FOLDER, $entityClassDetails->getRelativeNameWithoutSuffix()),
            'create_route' => strtolower("{$entityName}_create"),
            'update_route' => strtolower("{$entityName}_update"),
            'delete_route' => strtolower("{$entityName}_delete"),
        ];

        $generator->generateController(
            $controllerClassDetails->getFullName(),
            __DIR__.'/Resources/Controller.tpl.php',
            array_merge(
                $crudParameters,
                [
                    'uses_sort' => [$this, 'usesSort'],
                    'configurator_full_class_name' => $configuratorClassNameDetails->getFullName(),
                    'route_path' => Str::asRoutePath($controllerClassDetails->getRelativeNameWithoutSuffix()),
                    'list_route' => strtolower("{$entityName}_list"),
                    'is_crud' => $isCrud,
                ],
            ),
        );

        $generator->writeChanges();

        $this->writeSuccessMessage($io);
        $io->text(sprintf('Next: Check your new Admin by going to <fg=yellow>%s/</>', Str::asRoutePath($controllerClassDetails->getRelativeNameWithoutSuffix())));
        $io->text(sprintf('New Controller created at: <fg=yellow>%s/</>', Str::asClassName($controllerClassDetails->getFullName())));
        $io->text(sprintf('New Adapter class created at: <fg=yellow>%s/</>', Str::asClassName($adapterClassNameDetails->getFullName())));
        $io->text(sprintf('New Configurator class created at: <fg=yellow>%s/</>', Str::asClassName($configuratorClassNameDetails->getFullName())));

        if ($isCrud) {
            $io->text(sprintf('New Form class created at: <fg=yellow>%s/</>', Str::asClassName($formClassNameDetails->getFullName())));
        }

        if ($hasFilter) {
            $io->text(sprintf('New Filter Form class created at: <fg=yellow>%s/</>', Str::asClassName($filterClassNameDetails->getFullName())));
        }
    }

    /**
     * @param string[] $uses
     */
    public function usesSort(array $uses): string
    {
        uasort($uses, [$this, 'useSortAlphabetically']);

        $usesStatement = '';
        foreach ($uses as $use) {
            $usesStatement .= "use {$use};\n";
        }

        return $usesStatement;
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
        $dependencies->addClassDependency(
            DoctrineBundle::class, // @phpstan-ignore-line
            'orm-pack'
        );

        $dependencies->addClassDependency(
            Route::class,
            'router'
        );

        $dependencies->addClassDependency(
            AbstractType::class,
            'form'
        );

        $dependencies->addClassDependency(
            Request::class,
            'http-foundation'
        );
    }

    private function useSortAlphabetically(string $first, string $second): int
    {
        // Replace backslashes by spaces before sorting for correct sort order.
        $firstNamespace = str_replace('\\', ' ', $first);
        $secondNamespace = str_replace('\\', ' ', $second);

        return strcasecmp($firstNamespace, $secondNamespace);
    }
}
