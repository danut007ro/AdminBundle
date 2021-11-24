<?php

declare(strict_types=1);

namespace DG\AdminBundle;

use DG\AdminBundle\Adapter\CRUDAdapterInterface;
use DG\AdminBundle\UIAction\UIActionInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class ControllerHelper
{
    public const CSRF_NAME = 'dg-admin-csrf';

    public function __construct(
        private Environment $twig,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private TranslatorInterface $translator,
        private HttpKernelInterface $httpKernel,
    ) {
    }

    /**
     * Handles action request, by making a subrequest to `$controller` if needed.
     *
     * @param mixed[] $attributes
     */
    public function default(Request $request, string $controller, UIActionInterface $action, array $attributes = []): ?Response
    {
        if (!$request->isMethod(Request::METHOD_GET) || $request->isXmlHttpRequest()) {
            // Method is not GET or not an AJAX request, should be handled by user.
            return null;
        }

        $attributes = array_merge(
            $attributes,
            [
                '_controller' => $controller,
                UIActionInterface::KEY => $action,
            ],
        );

        $subrequest = $request->duplicate(null, null, $attributes);

        // Redirect to default controller.
        $response = $this->httpKernel->handle($subrequest, HttpKernelInterface::SUB_REQUEST);

        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }

    /**
     * @param mixed[] $templateParameters
     */
    public function create(Request $request, FormInterface $form, string $template = '@DGAdmin/dialog/create_update.html.twig', array $templateParameters = []): ?Response
    {
        return $this->formAction(
            $request,
            $form,
            $template,
            array_merge(
                ['title' => $this->translator->trans('Create', [], 'dg_admin')],
                $templateParameters,
            ),
        );
    }

    /**
     * @param mixed[] $templateParameters
     *
     * @phpstan-ignore-next-line
     */
    public function crudCreate(Request $request, FormInterface $form, CRUDAdapterInterface $crudAdapter, string $template = '@DGAdmin/dialog/create_update.html.twig', array $templateParameters = []): ?Response
    {
        if (null === $response = $this->create($request, $form, $template, $templateParameters)) {
            $crudAdapter->create($form->getData());
        }

        return $response;
    }

    /**
     * @param mixed[] $templateParameters
     */
    public function update(Request $request, FormInterface $form, string $template = '@DGAdmin/dialog/create_update.html.twig', array $templateParameters = []): ?Response
    {
        return $this->formAction(
            $request,
            $form,
            $template,
            array_merge(
                ['title' => $this->translator->trans('Update', [], 'dg_admin')],
                $templateParameters,
            ),
        );
    }

    /**
     * @param mixed[] $templateParameters
     *
     * @phpstan-ignore-next-line
     */
    public function crudUpdate(Request $request, FormInterface $form, CRUDAdapterInterface $crudAdapter, string $template = '@DGAdmin/dialog/create_update.html.twig', array $templateParameters = []): ?Response
    {
        if (null === $response = $this->update($request, $form, $template, $templateParameters)) {
            $crudAdapter->update($form->getData());
        }

        return $response;
    }

    /**
     * @param mixed[] $templateParameters
     */
    public function delete(Request $request, string $template = '@DGAdmin/dialog/delete.html.twig', array $templateParameters = []): ?Response
    {
        return $this->action(
            $request,
            $template,
            array_merge(
                [
                    'csrf_name' => 'dg-admin-csrf-delete',
                    'title' => $this->translator->trans('Delete confirmation', [], 'dg_admin'),
                    'message' => $this->translator->trans('Are you sure you want to delete?', [], 'dg_admin'),
                    'method' => Request::METHOD_DELETE,
                ],
                $templateParameters,
            ),
        );
    }

    /**
     * @param mixed[] $templateParameters
     *
     * @phpstan-ignore-next-line
     */
    public function crudDelete(Request $request, mixed $data, CRUDAdapterInterface $crudAdapter, string $template = '@DGAdmin/dialog/delete.html.twig', array $templateParameters = []): ?Response
    {
        if (null === $response = $this->delete($request, $template, $templateParameters)) {
            $crudAdapter->delete($data);
        }

        return $response;
    }

    /**
     * @param mixed[] $templateParameters
     */
    public function action(Request $request, string $template, array $templateParameters = []): ?Response
    {
        // Ensure "csrf_name" parameter is defined.
        if (!\array_key_exists('csrf_name', $templateParameters)) {
            $templateParameters['csrf_name'] = self::CSRF_NAME;
        }

        if (!$request->isMethod(Request::METHOD_GET) && $this->csrfTokenManager->isTokenValid(new CsrfToken($templateParameters['csrf_name'], (string) $request->request->get('_token')))) {
            // Action is valid, can continue operation.
            return null;
        }

        return $this->render(
            Response::HTTP_OK,
            null,
            $template,
            array_merge(
                ['url' => $request->getPathInfo()],
                $templateParameters,
            ),
        );
    }

    /**
     * @param mixed[] $templateParameters
     */
    public function formAction(Request $request, FormInterface $form, string $template, array $templateParameters = []): ?Response
    {
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Form is valid, can continue operation.
            return null;
        }

        return $this->render(
            $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK,
            $form,
            $template,
            $templateParameters,
        );
    }

    /**
     * @param mixed[] $templateParameters
     */
    public function render(int $statusCode = Response::HTTP_OK, ?FormInterface $form = null, string $template = '@DGAdmin/dialog/create_update.html.twig', array $templateParameters = []): Response
    {
        if (null !== $form) {
            $templateParameters = array_merge($templateParameters, [
                'data' => $form->getData(),
                'form' => $form->createView(),
            ]);
        }

        return new Response(
            $this->twig->render(
                $template,
                $templateParameters,
            ),
            $statusCode,
            ['Cache-Control' => 'no-store'],
        );
    }
}
