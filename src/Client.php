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

use Hyperf\Contract\IdGeneratorInterface;
use Hyperf\Contract\NormalizerInterface;
use Hyperf\Di\MethodDefinitionCollectorInterface;
use Hyperf\RpcClient\AbstractServiceClient;
use Hyperf\RpcClient\Exception\RequestException;
use Hyperf\RpcMultiplex\Contract\DataFetcherInterface;
use Hyperf\RpcMultiplex\Exception\InvalidArgumentException;
use Psr\Container\ContainerInterface;

class Client extends AbstractServiceClient
{
    /**
     * @var MethodDefinitionCollectorInterface
     */
    protected $methodDefinitionCollector;

    /**
     * @var string
     */
    protected $serviceInterface;

    /**
     * @var NormalizerInterface
     */
    private $normalizer;

    /**
     * @param $options = [
     *     'service_interface' => '',
     *     'load_balancer' => 'random',
     * ]
     */
    public function __construct(ContainerInterface $container, string $serviceName, string $protocol = 'jsonrpc-http', array $options = [])
    {
        $this->serviceName = $serviceName;
        $this->protocol = $protocol;
        $this->setOptions($options);
        parent::__construct($container);
        $this->normalizer = $container->get(NormalizerInterface::class);
        $this->methodDefinitionCollector = $container->get(MethodDefinitionCollectorInterface::class);

        if (! $this->dataFormatter instanceof DataFetcherInterface) {
            throw new InvalidArgumentException('The data formatter must instanceof DataFetcherInterface.');
        }
    }

    protected function __request(string $method, array $params, ?string $id = null)
    {
        if ($this->idGenerator instanceof IdGeneratorInterface && ! $id) {
            $id = $this->idGenerator->generate();
        }
        $response = $this->client->send($this->__generateData($method, $params, $id));
        if (! is_array($response)) {
            throw new RequestException('Invalid response.');
        }

        $fetched = $this->dataFormatter->fetch($response);

        $type = $this->methodDefinitionCollector->getReturnType($this->serviceInterface, $method);

        return $this->normalizer->denormalize($fetched, $type->getName());
    }

    public function __call(string $method, array $params)
    {
        return $this->__request($method, $params);
    }

    protected function setOptions(array $options): void
    {
        $this->serviceInterface = $options['service_interface'] ?? $this->serviceName;

        if (isset($options['load_balancer'])) {
            $this->loadBalancer = $options['load_balancer'];
        }
    }
}
