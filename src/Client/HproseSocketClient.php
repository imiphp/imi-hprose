<?php
namespace Imi\Hprose\Client;

use Imi\Rpc\Client\IService;
use Imi\Rpc\Client\IRpcClient;
use Imi\App;
use Imi\Event\Event;

/**
 * Hprose Socket 客户端
 */
class HproseSocketClient implements IRpcClient
{
    /**
     * Client
     *
     * @var \Hprose\Socket\Client
     */
    protected $client;

    /**
     * 配置
     *
     * @var array
     */
    protected $options;

    /**
     * 构造方法
     *
     * @param array $options 配置
     */
    public function __construct($options)
    {
        $this->options = $options;
    }

    /**
     * 打开
     * @return boolean
     */
    public function open()
    {
        $this->client = new \Hprose\Socket\Client($this->options['uris'], false);
        Event::trigger('IMI.RPC.HPROSE.CLIENT.OPEN', [
            'client'    =>  $this->client,
        ], $this);
    }

    /**
     * 关闭
     * @return void
     */
    public function close()
    {
        $this->client = null;
    }

    /**
     * 是否已连接
     * @return bool
     */
    public function isConnected(): bool
    {
        return true;
    }

    /**
     * 获取实例对象
     *
     * @return \Hprose\Socket\Client
     */
    public function getInstance()
    {
        return $this->client;
    }

    /**
     * 获取服务对象
     *
     * @param string $name 服务名
     * @return \Imi\Rpc\Client\IService
     */
    public function getService($name = null): \Imi\Rpc\Client\IService
    {
        return new HproseService($this, $name);
    }

    /**
     * 获取配置
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

}