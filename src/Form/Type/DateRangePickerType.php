<?php

declare(strict_types=1);

namespace DG\AdminBundle\Form\Type;

use DG\AdminBundle\DateFormat\DateFormats;
use Lexik\Bundle\FormFilterBundle\Filter\Condition\ConditionInterface;
use Lexik\Bundle\FormFilterBundle\Filter\Doctrine\DBALQuery;
use Lexik\Bundle\FormFilterBundle\Filter\Doctrine\ORMQuery;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToStringTransformer;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class DateRangePickerType extends AbstractType
{
    private const SEPARATOR = '>';

    private DateFormats $format;

    public function __construct(DateFormats $format)
    {
        $this->format = $format;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $transformer = new DateTimeToStringTransformer(
            $options['model_timezone'],
            $options['view_timezone'],
            \DateTimeInterface::ATOM,
        );

        $builder->addModelTransformer(new CallbackTransformer(
            static function ($dates) use ($transformer): string {
                if (null === $dates || '' === $dates) {
                    return '';
                }

                if (!\is_array($dates) || 2 !== \count($dates) || !$dates[0] instanceof \DateTimeInterface || !$dates[1] instanceof \DateTimeInterface) {
                    throw new TransformationFailedException('Expected an array with two \DateTimeInterface.');
                }

                /** @var string $from */
                $from = $transformer->transform($dates[0]);
                /** @var string $to */
                $to = $transformer->transform($dates[1]);
                if ('' === $from || '' === $to) {
                    return '';
                }

                return $from.self::SEPARATOR.$to;
            },
            static function ($value) use ($transformer): ?array {
                if (null === $value || '' === $value) {
                    return null;
                }

                if (!\is_string($value)) {
                    throw new TransformationFailedException('Expected a string.');
                }

                $parts = explode(self::SEPARATOR, $value, 2);
                if (2 !== \count($parts)) {
                    throw new TransformationFailedException(sprintf('Expected a string separated by a "%s".', self::SEPARATOR));
                }

                if (null === ($from = $transformer->reverseTransform($parts[0]))) {
                    throw new TransformationFailedException('Unable to transform FROM date.');
                }

                if (null === ($to = $transformer->reverseTransform($parts[1]))) {
                    throw new TransformationFailedException('Unable to transform TO date.');
                }

                // Update microseconds.
                $to->setTime((int) $to->format('H'), (int) $to->format('i'), (int) $to->format('s'), 999999);

                return [$from, $to];
            }
        ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired('format')
            ->setDefaults([
                'picker' => [],
                'model_timezone' => null,
                'view_timezone' => null,
                'apply_filter' => function ($query, string $field, array $values): ?ConditionInterface {
                    /** @var DBALQuery|ORMQuery $query */
                    if (null === $value = $values['value']) {
                        return null;
                    }

                    if (!isset($value[0]) || !isset($value[1])) {
                        return null;
                    }

                    return $query->createCondition((string) $query->getExpressionBuilder()->dateTimeInRange($field, $value[0], $value[1]));
                },
            ])
            ->setAllowedTypes('picker', 'array')
        ;
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        /** @var array<mixed> $view->vars */
        $vars = &$view->vars;
        $format = $this->format->getDateFormat($options['format']);

        $settings = $options['picker'];
        $settings['locale']['format'] = $format->getFormatMoment();
        $settings['separator'] = self::SEPARATOR;
        $settings['alwaysShowCalendars'] = true;

        if ($format->hasTime()) {
            $settings['timePicker'] = true;
            $settings['timePicker24Hour'] = $format->useTime24h();
        }

        $vars['attr']['data-dg-admin-datepicker'] = json_encode($settings, JSON_THROW_ON_ERROR, 512);
    }

    public function getParent(): string
    {
        return TextType::class;
    }
}
