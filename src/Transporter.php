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
namespace Hyperf\RpcMultiplex;

use Hyperf\LoadBalancer\LoadBalancerInterface;
use Hyperf\Rpc\Contract\TransporterInterface;
use Hyperf\RpcMultiplex\Exception\NotSupportException;
use Hyperf\Utils\Collection;
use Psr\Container\ContainerInterface;

class Transporter implements TransporterInterface
{
    /**
     * @var Collection
     */
    protected $config;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var SocketFactory
     */
    protected $factory;

    public function __construct(ContainerInterface $container, array $config = [])
    {
        $this->config = new Collection(array_replace_recursive($this->getDefaultConfig(), $config));
        $this->container = $container;
        $this->factory = make(SocketFactory::class, ['config' => $this->config]);
    }

    public function send(string $data)
    {
        return $this->factory->get()->request($data);
    }

    public function recv()
    {
        throw new NotSupportException('Recv is not supported.');
    }

    public function getLoadBalancer(): ?LoadBalancerInterface
    {
        return $this->factory->getLoadBalancer();
    }

    public function setLoadBalancer(LoadBalancerInterface $loadBalancer): TransporterInterface
    {
        $this->factory->setLoadBalancer($loadBalancer);
        return $this;
    }

    protected function getDefaultConfig(): array
    {
        return [
            'connect_timeout' => 5.0,
            'settings' => [
                'package_max_length' => 1024 * 1024 * 2,
            ],
            'recv_timeout' => 5.0,
            'retry_count' => 2,
            'retry_interval' => 100,
            'client_count' => 4,
        ];
    }
}
