<?php

namespace App\Tests\Unit\EventSubscriber;

use AmpProject\Optimizer\ErrorCollection;
use AmpProject\Optimizer\TransformationEngine;
use DG\BypassFinals;
use Hola\AmpToolboxBundle\EventSubscriber\AmpOptimizerSubscriber;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

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
        $event = $this->getEventNotAmpRequestMocked();
        $instance->onKernelResponse($event);
    }

    public function testTransformRequest()
    {
        $instance = $this->getInstance();
        $event = $this->getEventMasterRequestMocked();
        $instance->onKernelResponse($event);
    }

    /**
     * @param bool $transform
     * @param array $config
     * @return AmpOptimizerSubscriber
     */
    private function getInstance($transform = true, $config = []): AmpOptimizerSubscriber
    {
        $logger = $this->prophesize(LoggerInterface::class);

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

        return new AmpOptimizerSubscriber(
            $logger->reveal(),
            $transformationEngine->reveal(),
            $config
        );
    }

    /**
     * @return ResponseEvent
     */
    private function getEventMasterRequestMocked(): ResponseEvent
    {
        $response = $this->prophesize(Response::class);
        $response->getContent()->shouldBeCalled()->willReturn('<html ⚡></html>');
        $response->setContent(null)->shouldBeCalled();
        $response = $response->reveal();

        $event = $this->prophesize(ResponseEvent::class);
        $event->isMasterRequest()->shouldBeCalled()->willReturn(true);
        $event->getResponse()->shouldBeCalled()->willReturn($response);
        return $event->reveal();
    }

    /**
     * @return ResponseEvent
     */
    private function getEventNotAmpRequestMocked(): ResponseEvent
    {
        $response = $this->prophesize(Response::class);
        $response->getContent()->shouldBeCalled()->willReturn('<html></html>');
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
