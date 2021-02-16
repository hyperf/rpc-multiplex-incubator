<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace HyperfTest\Stub;

use Hyperf\Di\Container;
use Hyperf\Utils\ApplicationContext;
use Mockery;
use Multiplex\Constract\PackerInterface;
use Multiplex\Packer;

class ContainerStub
{
    public static function mockContainer(): Container
    {
        $container = Mockery::mock(Container::class);
        ApplicationContext::setContainer($container);
        $container->shouldReceive('get')->with(PackerInterface::class)->andReturn(new Packer());
        return $container;
    }
}
