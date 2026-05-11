<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Twig\Environment;

final class ExceptionListener implements EventSubscriberInterface
{
    public function __construct(private Environment $twig) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        // Only handle HttpExceptionInterface exceptions (4xx and 5xx)
        if (!$exception instanceof HttpExceptionInterface) {
            return;
        }

        $statusCode = $exception->getStatusCode();
        $statusText = Response::$statusTexts[$statusCode] ?? 'Unknown Error';

        try {
            if ($statusCode === 404) {
                $content = $this->twig->render('bundles/TwigBundle/Exception/error404.html.twig', [
                    'status_code' => $statusCode,
                    'status_text' => $statusText,
                ]);
            } else {
                $content = $this->twig->render('bundles/TwigBundle/Exception/error.html.twig', [
                    'status_code' => $statusCode,
                    'status_text' => $statusText,
                ]);
            }

            $response = new Response($content, $statusCode);
            $event->setResponse($response);
        } catch (\Exception $e) {
            // If template rendering fails, let the default exception handler take over
            return;
        }
    }
}
