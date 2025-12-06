<?php

declare(strict_types=1);

namespace ReactBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class ReactBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
