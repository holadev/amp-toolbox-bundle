<?php

namespace Hola\AmpToolboxBundle\Tests\Unit\EventSubscriber;

use AmpProject\Optimizer\Error\UnknownError;
use AmpProject\Optimizer\ErrorCollection;
use AmpProject\Optimizer\TransformationEngine;
use DG\BypassFinals;
use Hola\AmpToolboxBundle\EventSubscriber\AmpOptimizerSubscriber;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use ReflectionException;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class AmpOptimizerSubscriberTest extends TestCase
{
    public function setUp(): void
    {
        BypassFinals::enable();
    }

    /**
     * Test simple getSubscribedEvents function
     */
    public function testSubscribedEvents()
    {
        $events = AmpOptimizerSubscriber::getSubscribedEvents();
        $eventsExpected = [KernelEvents::RESPONSE => ['onKernelResponse', -10]];
        $this->assertEquals($events, $eventsExpected);
    }

    /**
     * Test not master request
     */
    public function testNotMasterRequest()
    {
        $instance = $this->getInstanceNotMasterRequest();
        $event = $this->getEventNotMasterRequestMocked();
        $instance->onKernelResponse($event);
    }

    /**
     * Provide instance to test with not master request and test calls
     * @return AmpOptimizerSubscriber
     */
    private function getInstanceNotMasterRequest(): AmpOptimizerSubscriber
    {
        $logger = $this->prophesize(LoggerInterface::class);

        $transformationEngine = $this->prophesize(TransformationEngine::class);
        $transformationEngine->optimizeHtml(Argument::type('string'), Argument::type(ErrorCollection::class))
            ->shouldNotBeCalled();

        return new AmpOptimizerSubscriber(
            $logger->reveal(),
            $transformationEngine->reveal(),
            ['transform_enabled' => true]
        );
    }

    /**
     * Provide response event to test with not master request and test calls
     * @return ResponseEvent
     */
    private function getEventNotMasterRequestMocked(): ResponseEvent
    {
        $event = $this->prophesize(ResponseEvent::class);
        $event->isMasterRequest()->shouldBeCalled()->willReturn(false);
        $event->getResponse()->shouldNotBeCalled();
        return $event->reveal();
    }

    /**
     * Test request without html amp format or content type
     */
    public function testNotAmpRequest()
    {
        $instance = $this->getInstanceNotAmpRequest();
        $event = $this->getEventNotAmpRequestMocked('image/jpeg');
        $instance->onKernelResponse($event);

        $event = $this->getEventNotAmpRequestMocked('text/html', '<html></html>');
        $instance->onKernelResponse($event);
    }

    /**
     * Provide instance to test with not html amp request and test calls
     * @return AmpOptimizerSubscriber
     */
    private function getInstanceNotAmpRequest(): AmpOptimizerSubscriber
    {
        $logger = $this->prophesize(LoggerInterface::class);

        $transformationEngine = $this->prophesize(TransformationEngine::class);
        $transformationEngine->optimizeHtml(Argument::type('string'), Argument::type(ErrorCollection::class))
            ->shouldNotBeCalled();

        return new AmpOptimizerSubscriber(
            $logger->reveal(),
            $transformationEngine->reveal(),
            ['transform_enabled' => true]
        );
    }

    /**
     * Provide response event to test with not html amp request and test calls
     * @param string $contentType
     * @param string $content
     * @return ResponseEvent
     */
    private function getEventNotAmpRequestMocked($contentType = 'text/html', $content = '<html ⚡></html>'): ResponseEvent
    {
        $headers = $this->prophesize(ParameterBag::class);
        $headers->get(Argument::exact('Content-type'))->willReturn($contentType);

        $response = $this->prophesize(Response::class);
        if ($contentType === 'text/html') {
            $response->getContent()->shouldBeCalled()->willReturn($content);
        }
        if ($contentType === 'image/jpeg') {
            $response->getContent()->shouldNotBeCalled();
        }

        $response->headers = $headers;
        $response = $response->reveal();

        $event = $this->prophesize(ResponseEvent::class);
        $event->isMasterRequest()->shouldBeCalled()->willReturn(true);
        $event->getResponse()->shouldBeCalled()->willReturn($response);
        return $event->reveal();
    }

    /**
     * Test normal operation
     */
    public function testTransformRequest()
    {
        $instance = $this->getInstance();
        $event = $this->getEventMasterRequestMocked();
        $instance->onKernelResponse($event);
    }

    /**
     * Provide instance to test normal operation and test calls
     * @return AmpOptimizerSubscriber
     */
    private function getInstance(): AmpOptimizerSubscriber
    {
        $logger = $this->prophesize(LoggerInterface::class);

        $transformationEngine = $this->prophesize(TransformationEngine::class);
        $transformationEngine->optimizeHtml(Argument::type('string'), Argument::type(ErrorCollection::class))
            ->shouldBeCalled();

        return new AmpOptimizerSubscriber(
            $logger->reveal(),
            $transformationEngine->reveal(),
            ['transform_enabled' => true]
        );
    }


    /**
     * Test disable transform from config
     */
    public function testNotConfigDisableRequest()
    {
        $instance = $this->getInstanceConfigDisabledRequest();
        $event = $this->getEventConfigDisabledMocked();
        $instance->onKernelResponse($event);
    }

    /**
     * Provide instance to test disable transform from config and test calls
     * @return AmpOptimizerSubscriber
     */
    private function getInstanceConfigDisabledRequest(): AmpOptimizerSubscriber
    {
        $logger = $this->prophesize(LoggerInterface::class);

        $transformationEngine = $this->prophesize(TransformationEngine::class);
        $transformationEngine->optimizeHtml(Argument::type('string'), Argument::type(ErrorCollection::class))
            ->shouldNotBeCalled();

        return new AmpOptimizerSubscriber(
            $logger->reveal(),
            $transformationEngine->reveal(),
            []
        );
    }

    /**
     * Provide response event to test disable transform from config and test calls
     * @return ResponseEvent
     */
    private function getEventConfigDisabledMocked(): ResponseEvent
    {
        $event = $this->prophesize(ResponseEvent::class);
        $event->isMasterRequest()->shouldNotBeCalled()->willReturn(false);
        $event->getResponse()->shouldNotBeCalled();
        return $event->reveal();
    }


    /**
     * Test normal operation with error log
     */
    public function testTransformRequestWithErrorLog()
    {
        $instance = $this->getInstanceWithErrorLog();
        $event = $this->getEventMasterRequestMocked();
        $instance->onKernelResponse($event);
    }

    /**
     * Provide instance to test normal operation with error loga nd test calls
     * @return AmpOptimizerSubscriber
     * @throws ReflectionException
     */
    private function getInstanceWithErrorLog(): AmpOptimizerSubscriber
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $logger->error(Argument::any())->shouldBeCalled();

        $transformationEngine = $this->prophesize(TransformationEngine::class);
        $transformationEngine->optimizeHtml(Argument::type('string'), Argument::type(ErrorCollection::class))
            ->shouldBeCalled();

        $instance = new AmpOptimizerSubscriber(
            $logger->reveal(),
            $transformationEngine->reveal(),
            ['transform_enabled' => true]
        );

        $reflection = new \ReflectionClass($instance);
        $property = $reflection->getProperty('errorCollection');
        $property->setAccessible(true);
        $errorCollection = new ErrorCollection();
        $errorCollection->add(new UnknownError('example error message'));
        $property->setValue($instance, $errorCollection);

        return $instance;
    }

    /**
     * Provide response event to test normal operation log and test calls
     * @return ResponseEvent
     */
    private function getEventMasterRequestMocked(): ResponseEvent
    {
        $headers = $this->prophesize(ParameterBag::class);
        $headers->get(Argument::exact('Content-type'))->willReturn('text/html');

        $response = $this->prophesize(Response::class);
        $response->getContent()->shouldBeCalled()->willReturn('<html ⚡></html>');
        $response->setContent(null)->shouldBeCalled();
        $response->headers = $headers;
        $response = $response->reveal();

        $event = $this->prophesize(ResponseEvent::class);
        $event->isMasterRequest()->shouldBeCalled()->willReturn(true);
        $event->getResponse()->shouldBeCalled()->willReturn($response);
        return $event->reveal();
    }

    /**
     * Test disabled by property
     */
    public function testDisabledByProperty()
    {
        $instance = $this->getInstanceDisabledByProperty();
        $instance->setEnabled(false);
        $event = $this->getEventDisabledByPropertyMocked();
        $instance->onKernelResponse($event);
    }

    /**
     * Provide instance to test with disabled by property and test calls
     * @return AmpOptimizerSubscriber
     */
    private function getInstanceDisabledByProperty(): AmpOptimizerSubscriber
    {
        $logger = $this->prophesize(LoggerInterface::class);

        $transformationEngine = $this->prophesize(TransformationEngine::class);
        $transformationEngine->optimizeHtml(Argument::type('string'), Argument::type(ErrorCollection::class))
            ->shouldNotBeCalled();

        return new AmpOptimizerSubscriber(
            $logger->reveal(),
            $transformationEngine->reveal(),
            ['transform_enabled' => true]
        );
    }

    /**
     * Provide response event to test with disabled by property and test calls
     * @return ResponseEvent
     */
    private function getEventDisabledByPropertyMocked(): ResponseEvent
    {
        $event = $this->prophesize(ResponseEvent::class);
        $event->isMasterRequest()->shouldNotBeCalled();
        $event->getResponse()->shouldNotBeCalled();
        return $event->reveal();
    }
}
