<?php

namespace App\Tests\Unit\EventListener;

use App\EventListener\StaticContentListener;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class AmpOptimizerSubscriberTest extends TestCase
{
    public function testOk()
    {
        $this->assertTrue(true);
    }
}
