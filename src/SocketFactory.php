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
use Hyperf\RpcMultiplex\Exception\NoAvailableNodesException;
use Hyperf\Utils\Arr;
use Hyperf\Utils\Collection;
use Psr\Container\ContainerInterface;

class SocketFactory
{
    /**
     * @var null|LoadBalancerInterface
     */
    protected $loadBalancer;

    /**
     * @var Socket[]
     */
    protected $clients = [];

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var Collection
     */
    protected $config;

    public function __construct(ContainerInterface $container, Collection $config)
    {
        $this->container = $container;
        $this->config = $config;
    }

    public function getLoadBalancer(): ?LoadBalancerInterface
    {
        return $this->loadBalancer;
    }

    public function setLoadBalancer(LoadBalancerInterface $loadBalancer)
    {
        $this->loadBalancer = $loadBalancer;
    }

    public function refresh(): void
    {
        $nodes = $this->getNodes();
        $nodeCount = count($nodes);
        $count = $this->getCount();
        for ($i = 0; $i < $count; ++$i) {
            if (! isset($this->clients[$i])) {
                $this->clients[$i] = make(Socket::class);
            }
            $client = $this->clients[$i];
            $node = $nodes[$i % $nodeCount];
            $client->setName($node->host)->setPort($node->port);
        }
    }

    public function get(): Socket
    {
        if (count($this->clients) === 0) {
            $this->refresh();
        }

        return Arr::random($this->clients);
    }

    protected function getNodes(): array
    {
        $nodes = $this->getLoadBalancer()->getNodes();
        if (empty($nodes)) {
            throw new NoAvailableNodesException();
        }

        return $nodes;
    }

    protected function getCount(): int
    {
        return (int) $this->config->get('client_count', 4);
    }
}
