<?php

declare(strict_types=1);

namespace Hola\AmpToolboxBundle;

use Hola\AmpToolboxBundle\DependencyInjection\AmpToolboxExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class HolaAmpToolboxBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new AmpToolboxExtension();
    }
}