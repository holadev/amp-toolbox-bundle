<?php

namespace App\Tests\Unit\EventSubscriber;

use AmpProject\Optimizer\ErrorCollection;
use AmpProject\Optimizer\TransformationEngine;
use DG\BypassFinals;
use Hola\AmpToolboxBundle\EventSubscriber\AmpOptimizerSubscriber;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use AmpProject\Optimizer\Error\UnknownError;

class AmpOptimizerSubscriberTest extends TestCase
{
    public function setUp(): void
    {
        BypassFinals::enable();
    }

    public function testSubscribedEvents()
    {
        $events = AmpOptimizerSubscriber::getSubscribedEvents();
        $eventsExpected = [KernelEvents::RESPONSE => ['onKernelResponse', -10]];
        $this->assertEquals($events, $eventsExpected);
    }

    public function testNotMasterRequest()
    {
        $instance = $this->getInstance(false);
        $event = $this->getEventNotMasterRequestMocked();
        $instance->onKernelResponse($event);
    }

    public function testNotAmpRequest()
    {
        $instance = $this->getInstance(false);
        $event = $this->getEventNotAmpRequestMocked('text/html');
        $instance->onKernelResponse($event);

        $event = $this->getEventNotAmpRequestMocked('image/jpeg');
        $instance->onKernelResponse($event);
    }

    public function testTransformRequest()
    {
        $instance = $this->getInstance();
        $event = $this->getEventMasterRequestMocked();
        $instance->onKernelResponse($event);
    }

    public function testTransformDisabledRequest()
    {
        $instance = $this->getInstance(false, []);
        $event = $this->getEventMasterRequestTransformDisabledMocked();
        $instance->onKernelResponse($event);
    }

    /**
     * @param bool $transform
     * @param array $config
     * @return AmpOptimizerSubscriber
     */
    private function getInstance($transform = true, $config = ['transform_enabled' => true]): AmpOptimizerSubscriber
    {
        $logger = $this->prophesize(LoggerInterface::class);
        if($transform === true && $config === ['transform_enabled' => true]) {
            $logger->error(Argument::any())->shouldBeCalled();
        }
        $transformationEngine = $this->prophesize(TransformationEngine::class);

        if ($transform) {
            $transformationEngine->optimizeHtml(
                Argument::type('string'),
                Argument::type(ErrorCollection::class)
            )->shouldBeCalled();
        }

        if (!$transform) {
            $transformationEngine->optimizeHtml(
                Argument::type('string'),
                Argument::type(ErrorCollection::class)
            )->shouldNotBeCalled();
        }

        $instance = new AmpOptimizerSubscriber(
            $logger->reveal(),
            $transformationEngine->reveal(),
            $config
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
     * @return ResponseEvent
     */
    private function getEventMasterRequestTransformDisabledMocked(): ResponseEvent
    {
        $response = $this->prophesize(Response::class);
        $response->getContent()->shouldNotBeCalled();
        $response->setContent(null)->shouldNotBeCalled();
        $response = $response->reveal();

        $event = $this->prophesize(ResponseEvent::class);
        $event->isMasterRequest()->shouldNotBeCalled()->willReturn(true);
        $event->getResponse()->shouldNotBeCalled()->willReturn($response);
        return $event->reveal();
    }

    /**
     * @param string $contentType
     * @return ResponseEvent
     */
    private function getEventNotAmpRequestMocked($contentType = 'text/html'): ResponseEvent
    {
        $headers = $this->prophesize(ParameterBag::class);
        $headers->get(Argument::exact('Content-type'))->willReturn($contentType);

        $response = $this->prophesize(Response::class);
        if ($contentType === 'text/html') {
            $response->getContent()->shouldBeCalled()->willReturn('<html></html>');
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
     * @return ResponseEvent
     */
    private function getEventNotMasterRequestMocked(): ResponseEvent
    {
        $event = $this->prophesize(ResponseEvent::class);
        $event->isMasterRequest()->shouldBeCalled()->willReturn(false);
        $event->getResponse()->shouldNotBeCalled();
        return $event->reveal();
    }
}
