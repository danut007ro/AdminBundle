<?php

declare(strict_types=1);

namespace DG\AdminBundle\Form\Type;

use DG\AdminBundle\Form\AppendChoiceLoader;
use DG\AdminBundle\Form\DataTransformer\TagToValueTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\Factory\ChoiceListFactoryInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Select2TagsType extends AbstractType
{
    public function __construct(private ChoiceListFactoryInterface $choiceListFactory)
    {
    }

    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (0 !== \count($options['choices'])) {
            return;
        }

        // Reset view transformers in order ro remove choice value transformer.
        // We need it removed because our choices are empty.
//        $builder->resetViewTransformers();

        // Add our specific choice transformer.
        // Choices will be added dynamically in order to pass validation.
        $transformer = new TagToValueTransformer($options);
        $builder->addViewTransformer($transformer);

        $builder
            ->addEventListener(FormEvents::PRE_SET_DATA, static function (FormEvent $event) use ($transformer): void {
                /** @var null|string[] $data */
                $data = $event->getData();
                foreach ($data ?? [] as $entry) {
                    if (!$transformer->hasChoice($entry)) {
                        $transformer->addChoice($entry, $entry);
                    }
                }
            }, 1)
            ->addEventListener(FormEvents::PRE_SUBMIT, static function (FormEvent $event) use ($transformer): void {
                foreach ($event->getData() ?? [] as $entry) {
                    if (!$transformer->hasChoice($entry)) {
                        $transformer->addChoice($entry, $entry);
                    }
                }
            }, 1)
        ;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        if (0 !== \count($options['choices']) || !\is_array($data = $form->getNormData()) || 0 === \count($data)) {
            return;
        }

        $data = array_combine($data, $data);

        // Build choices only with current value.
        $choiceListView = $this->choiceListFactory->createView(
            $this->choiceListFactory->createListFromChoices($data, $options['choice_value']),
            $options['preferred_choices'],
            $options['choice_label'],
            $options['choice_name'],
            $options['group_by'],
            $options['choice_attr'],
        );

        foreach ($choiceListView->choices as $choiceView) {
            $choiceView->attr = ['selected' => 'selected'];
        }

        $view->vars['choices'] = $choiceListView->choices;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => [],
            'multiple' => true,
            'dg_admin_select2' => true,
            'attr' => ['data-tags' => true],
            'choice_loader' => new AppendChoiceLoader(),
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
