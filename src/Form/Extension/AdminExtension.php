<?php

declare(strict_types=1);

namespace DG\AdminBundle\Form\Extension;

use DG\AdminBundle\Exception\InvalidArgumentException;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class AdminExtension extends AbstractTypeExtension
{
    /**
     * @return iterable<class-string<FormTypeInterface>>
     */
    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }

    /**
     * @param array<string, mixed> $options
     */
    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        if (!$form->isRoot()) {
            // No need to process since this is not the root element.
            return;
        }

        $accessor = PropertyAccess::createPropertyAccessor();
        /** @var FormView[] $view->children */
        $children = &$view->children;
        foreach ($children as $child) {
            $this->processConditionize($child, $accessor);
            $this->processSelect2($child, $accessor);
        }
    }

    private function processConditionize(FormView $view, PropertyAccessorInterface $accessor): void
    {
        $vars = &$view->vars;
        if (null !== $condition = ($vars['attr']['data-dg-admin-condition'] ?? null)) {
            unset($vars['attr']['data-dg-admin-condition']);
            $vars['attr']['data-condition'] = $this->parseFieldName($condition, $view, $accessor);
        }

        /** @var FormView[] $view->children */
        $children = &$view->children;
        foreach ($children as $child) {
            $this->processConditionize($child, $accessor);
        }
    }

    private function processSelect2(FormView $view, PropertyAccessorInterface $accessor): void
    {
        $vars = &$view->vars;
        if ([] !== $fieldParams = ($vars['attr']['data-dg-admin-select2-field-params'] ?? [])) {
            $fields = [];
            foreach ($fieldParams as $fieldName => $paramName) {
                $fields[$this->parseFieldName($fieldName, $view, $accessor)] = $paramName;
            }

            $vars['attr']['data-dg-admin-select2-field-params'] = json_encode($fields);
        }

        /** @var FormView[] $view->children */
        $children = &$view->children;
        foreach ($children as $child) {
            $this->processSelect2($child, $accessor);
        }
    }

    private function parseFieldName(string $fieldName, FormView $view, PropertyAccessorInterface $accessor): string
    {
        $replaces = $matches = [];
        preg_match_all('/\${(.*?)}/', $fieldName, $matches);
        foreach ($matches[1] as $match => $field) {
            // Check if field starts with '#' so it will be processed as id instead of name.
            $id = false;
            if (str_starts_with($field, '#')) {
                $id = true;
                $field = substr($field, 1);
            }

            // Retrieve all field vars and build field name either from id or full_name.
            try {
                $fieldVars = $accessor->getValue($view, "{$field}.vars");
            } catch (NoSuchPropertyException $e) {
                throw new InvalidArgumentException(sprintf('Cannot read property "%s" from FormView.', "{$field}.vars"), $e->getCode(), $e);
            }

            $name = $fieldVars[$id ? 'id' : 'full_name'];
            if ('' === $name) {
                continue;
            }

            if ($id) {
                $name = "#{$name}";
            }

            $replaces[$matches[0][$match]] = $name;
        }

        // Set replaced condition attribute.
        return strtr($fieldName, $replaces);
    }
}
