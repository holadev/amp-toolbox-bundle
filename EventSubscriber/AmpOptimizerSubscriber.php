<?php

namespace Hola\AmpToolboxBundle\EventSubscriber;

use AmpProject\Optimizer\ErrorCollection;
use AmpProject\Optimizer\TransformationEngine;
use Psr\Log\LoggerInterface;
use Sunra\PhpSimple\HtmlDomParser;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Listen response event and optimize html
 * Class AmpOptimizerSubscriber
 * @package Hola\AmpToolboxBundle\EventSubscriber
 */
class AmpOptimizerSubscriber implements EventSubscriberInterface
{

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var TransformationEngine
     */
    private $transformationEngine;

    /**
     * @var array
     */
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
     * @return void
     */
    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        if (!$this->isAmpHtml($event->getResponse())) {
            return;
        }

        $errorCollection = new ErrorCollection();

        $optimizedHtml = $this->transformationEngine->optimizeHtml(
            $event->getResponse()->getContent(),
            $errorCollection
        );

        $event->getResponse()->setContent($optimizedHtml);
    }

    /**
     * @param Response $response
     * @return bool
     */
    private function isAmpHtml(Response $response): bool
    {
        $contentType = $response->headers->get('Content-type');
        if (strpos($contentType, 'text/html') === false) {
            return false;
        }

        $content = $response->getContent();
        $dom = HtmlDomParser::str_get_html($content);
        $htmlElementAttrs = $dom->find('html', 0)->getAllAttributes();
        if (empty(array_intersect(['⚡', 'amp'], array_keys($htmlElementAttrs)))) {
            return false;
        }

        return true;
    }
}