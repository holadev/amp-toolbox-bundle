<?php

declare(strict_types=1);

namespace Hola\AmpToolboxBundle;

use Hola\AmpToolboxBundle\DependencyInjection\AmpToolboxExtension;
use PHPUnit\Framework\TestCase;

class HolaAmpToolboxBundleTest extends TestCase
{
    public function testGetContainerExtension()
    {
        $instance = new HolaAmpToolboxBundle();
        $extension = $instance->getContainerExtension();

        $this->assertInstanceOf(AmpToolboxExtension::class, $extension);
    }
}