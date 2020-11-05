<?php

namespace Hola\AmpToolboxBundle\EventSubscriber;

use AmpProject\Optimizer\ErrorCollection;
use AmpProject\Optimizer\TransformationEngine;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class AmpOptimizerSubscriber implements EventSubscriberInterface
{
    /** @var LoggerInterface */
    private $logger;

    /** @var TransformationEngine */
    private $transformationEngine;

    /** @var array */
    private $config;

    /**
     * AmpOptimizerSubscriber constructor.
     * @param LoggerInterface $logger
     * @param TransformationEngine $transformationEngine
     * @param $config
     */
    public function __construct(LoggerInterface $logger, TransformationEngine $transformationEngine, $config)
    {
        $this->config = $config;
        $this->logger = $logger;
        $this->transformationEngine = $transformationEngine;
    }

    /**
     * @return array|array[]
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', -10],
        ];
    }

    /**
     * @param ResponseEvent $event
     */
    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $response = $event->getResponse();
        $errorCollection = new ErrorCollection();

        $optimizedHtml = $this->transformationEngine->optimizeHtml($response->getContent(), $errorCollection);

        $response->setContent($optimizedHtml);
    }
}