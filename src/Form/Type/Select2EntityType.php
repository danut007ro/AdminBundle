<?php

declare(strict_types=1);

namespace DG\AdminBundle\Form\Type;

use DG\AdminBundle\Column\ValueExtractor\ValueExtractorInterface;
use DG\AdminBundle\Form\DataTransformer\EntitiesToPropertiesTransformer;
use DG\AdminBundle\Form\DataTransformer\EntityToPropertyTransformer;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\RuntimeException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Select2EntityType extends AbstractType
{
    public function __construct(protected ManagerRegistry $registry)
    {
    }

    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $em = $this->registry->getManagerForClass($options['class']);
        if (!$em instanceof EntityManagerInterface) {
            throw new RuntimeException(sprintf('Class "%s" seems not to be a managed Doctrine entity. Did you forget to map it?', $options['class']));
        }

        if ($options['multiple']) {
            $transformer = new EntitiesToPropertiesTransformer(
                $em,
                $options['class'],
                $options['id_property'],
                $options['text_value'],
                $options['entity_validator'],
                $options['query_builder_callback'],
                $options['query_callback'],
            );
        } else {
            $transformer = new EntityToPropertyTransformer(
                $em,
                $options['class'],
                $options['id_property'],
                $options['text_value'],
                $options['entity_validator'],
                $options['query_builder_callback'],
                $options['query_callback'],
            );
        }

        $builder->addViewTransformer($transformer, true);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        /** @var array<string, mixed> $view->vars */
        $vars = &$view->vars;

        if ($options['multiple']) {
            $vars['attr']['multiple'] = 'multiple';
            $vars['full_name'] .= '[]'; // @phpstan-ignore-line
        }

        // Set field params.
        if ([] !== $options['dg_admin_select2_field_params']) {
            // This will be handled in AdminExtension.
            $vars['attr']['data-dg-admin-select2-field-params'] = $options['dg_admin_select2_field_params'];
        }

        if (\is_string($options['dg_admin_select2'])) {
            $key = $options['dg_admin_select2_autocomplete'] ? 'data-ajax--url' : 'data-ajax-reload-url';
            $vars['attr'][$key] = $options['dg_admin_select2'];
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired('class')
            ->setDefaults([
                'compound' => false,
                'multiple' => false,
                'query_builder_callback' => null,
                'query_callback' => null,
                'id_property' => 'id',
                'text_value' => null,
                'entity_validator' => null,
                'dg_admin_select2' => '',
                'dg_admin_select2_autocomplete' => true,
                'dg_admin_select2_field_params' => [],
            ])
            ->setAllowedTypes('class', 'string')
            ->setAllowedTypes('multiple', 'bool')
            ->setAllowedTypes('query_builder_callback', ['null', 'callable'])
            ->setAllowedTypes('query_callback', ['null', 'callable'])
            ->setAllowedTypes('id_property', 'string')
            ->setAllowedTypes('text_value', ['null', ValueExtractorInterface::class])
            ->setAllowedTypes('entity_validator', ['null', 'callable'])
            ->setAllowedTypes('dg_admin_select2', ['null', 'string'])
            ->setAllowedTypes('dg_admin_select2_autocomplete', 'bool')
            ->setAllowedTypes('dg_admin_select2_field_params', 'array')
            ->setInfo(
                'dg_admin_select2',
                <<<'INFO'
                Specify how should initialize select2.
                If `NULL` is specified then initialize without ajax.
                If a string is given then initialize as ajax. If empty string then current page url will be used (from `window.location`).
                INFO
            )
            ->setInfo('dg_admin_select2_autocomplete', 'Specify if the select2 should autocomplete its options or load them all at once.')
            ->setInfo(
                'dg_admin_select2_field_params',
                <<<'INFO'
                Specify a mapping between form fields and parameter names to add to ajax requests.
                Key is the field name specified as `parent.children[anotherElement]`.
                Value is the name to be added to request.
                This is needed because the name will also contain form name and you don't want to hardcode that.
                INFO
            )
        ;
    }

    public function getBlockPrefix(): string
    {
        return 'dg_admin_select2_entity';
    }
}
