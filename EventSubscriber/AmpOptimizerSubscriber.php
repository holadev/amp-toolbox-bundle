<?php

namespace Hola\AmpToolboxBundle\EventSubscriber;

use AmpProject\Optimizer\ErrorCollection;
use AmpProject\Optimizer\TransformationEngine;
use Psr\Log\LoggerInterface;
use Sunra\PhpSimple\HtmlDomParser;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
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
     * @var ErrorCollection
     */
    private $errorCollection;

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
        $this->errorCollection = new ErrorCollection();
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
        if (!array_key_exists('transform_enabled', $this->config) ||
            false === $this->config['transform_enabled']) {
            return;
        }

        if (!$event->isMasterRequest()) {
            return;
        }

        if (!$this->isAmpHtml($event->getResponse(), $event->getRequest())) {
            return;
        }

        $optimizedHtml = $this->transformationEngine->optimizeHtml(
            $event->getResponse()->getContent(),
            $this->errorCollection
        );

        $this->handleErrors();

        $event->getResponse()->setContent($optimizedHtml);
    }

    /**
     * @param Response $response
     * @param Request $request
     * @return bool
     */
    private function isAmpHtml(Response $response, Request $request): bool
    {
        $pathInfo = pathInfo($request->getUri());
        if (isset($pathInfo['extension']) && $pathInfo['extension'] !== 'html') {
            return false;
        }

        $contentType = $response->headers->get('Content-type');
        if (strpos($contentType, 'text/html') === false) {
            return false;
        }

        $content = $response->getContent();
        $dom = HtmlDomParser::str_get_html($content);

        if ($dom === false) {
            $this->logger->error('Content can not be parsed by HtmlDomParser');
            return false;
        }

        $htmlElement = $dom->find('html', 0);
        if (null === $htmlElement) {
            return false;
        }

        $htmlElementAttrs = $htmlElement->getAllAttributes();
        if (empty(array_intersect(['⚡', 'amp'], array_keys($htmlElementAttrs)))) {
            return false;
        }

        return true;
    }

    private function handleErrors(): void
    {
        if ($this->errorCollection->count() > 0) {
            foreach ($this->errorCollection as $error) {
                $this->logger->error(sprintf(
                    "AMP-Optimizer Error code: %s\nError Message: %s\n",
                    $error->getCode(),
                    $error->getMessage()
                ));
            }
        }
    }

    /**
     * @param bool $enabled
     */
    public function setEnabled(bool $enabled): void
    {
        $this->config['transform_enabled'] = $enabled;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->config['transform_enabled'];
    }
}