<?php
namespace Imi\Server\Hprose;

use Imi\App;
use Imi\Log\Log;
use Imi\Server\Base;
use Imi\ServerManage;
use Imi\RequestContext;
use Imi\Event\EventParam;
use Imi\Pool\PoolManager;
use Imi\Server\Event\Param\CloseEventParam;
use Imi\Server\Event\Param\BufferEventParam;
use Imi\Server\Event\Param\ConnectEventParam;
use Imi\Server\Event\Param\ReceiveEventParam;
use Swoole\Coroutine;

class Server extends Base
{
    /**
     * Hprose Service
     *
     * @var \Hprose\Swoole\Socket\Service
     */
    private $hproseService;

    private $isHookHproseOn = false;

    /**
     * 创建 swoole 服务器对象
     * @return void
     */
    protected function createServer()
    {
        $config = $this->getServerInitConfig();
        $this->swooleServer = new \swoole_server($config['host'], $config['port'], $config['mode'], $config['sockType']);
        $this->hproseService = new \Hprose\Swoole\Socket\Service;
        $this->parseConfig($config);
    }

    /**
     * 从主服务器监听端口，作为子服务器
     * @return void
     */
    protected function createSubServer()
    {
        $config = $this->getServerInitConfig();
        $this->swooleServer = ServerManage::getServer('main')->getSwooleServer();
        $this->swoolePort = $this->swooleServer->addListener($config['host'], $config['port'], $config['sockType']);
        $this->swoolePort->set([]);
        $this->hproseService = new \Hprose\Swoole\Socket\Service;
        $this->parseConfig($config);
        $this->hproseService->onBeforeInvoke = function($name, $args, $byref, \stdClass $context){
            RequestContext::create();
            RequestContext::set('server', $this);
            $this->trigger('BeforeInvoke', [
                'name'      => $name,
                'args'      => &$args,
                'byref'     => $byref,
                'context'   => $context,
            ], $this);
        };
        $this->hproseService->onAfterInvoke = function($name, $args, $byref, &$result, \stdClass $context) {
            $this->trigger('AfterInvoke', [
                'name'      => $name,
                'args'      => $args,
                'byref'     => $byref,
                'context'   => $context,
                'result'    => &$result,
            ], $this);
            if($result instanceof \Imi\Model\BaseModel)
            {
                $result = $result->toArray();
            }
            PoolManager::destroyCurrentContext();
            RequestContext::destroy();
        };
        $this->hproseService->onSendError = function(\Throwable $error, \stdClass $context) {
            Log::error($error->getMessage(), [
                'trace'     => $error->getTrace(),
                'errorFile' => $error->getFile(),
                'errorLine' => $error->getLine(),
            ]);
            PoolManager::destroyCurrentContext();
            RequestContext::destroy();
        };
    }

    /**
     * 获取服务器初始化需要的配置
     * @return array
     */
    protected function getServerInitConfig()
    {
        return [
            'host'      => isset($this->config['host']) ? $this->config['host'] : '0.0.0.0',
            'port'      => isset($this->config['port']) ? $this->config['port'] : 8080,
            'sockType'  => isset($this->config['sockType']) ? (SWOOLE_SOCK_TCP | $this->config['sockType']) : SWOOLE_SOCK_TCP,
            'mode'      => isset($this->config['mode']) ? $this->config['mode'] : SWOOLE_PROCESS,
        ];
    }

    /**
     * 处理服务器配置
     *
     * @return void
     */
    private function parseConfig($config)
    {
        if (SWOOLE_UNIX_STREAM !== $config['sockType'])
        {
            $this->config['configs']['open_tcp_nodelay'] = true;
        }
        $this->config['configs']['open_eof_check'] = false;
        $this->config['configs']['open_length_check'] = false;
        $this->config['configs']['open_eof_split'] = false;
    }

    /**
     * 事件监听
     * @param string $name 事件名称
     * @param mixed $callback 回调，支持回调函数、基于IEventListener的类名
     * @param int $priority 优先级，越大越先执行
     * @return void
     */
    public function on($name, $callback, $priority = 0)
    {
        if($this->isHookHproseOn)
        {
            parent::on($name, function(EventParam $e) use($callback) {
                $data = $e->getData();
                $data['server'] = $this->swooleServer;
                $callback(...array_values($data));
            }, $priority);
        }
        else
        {
            parent::on($name, $callback, $priority);
        }
    }

    /**
     * 绑定服务器事件
     * @return void
     */
    protected function __bindEvents()
    {
        $server = $this->swoolePort ?? $this->swooleServer;

        $this->isHookHproseOn = true;
        $this->hproseService->socketHandle($this);
        $this->isHookHproseOn = false;

        $server->on('connect', function(\swoole_server $server, $fd, $reactorID){
            try{
                $this->trigger('connect', [
                    'server'    => $this,
                    'fd'        => $fd,
                    'reactorID' => $reactorID,
                ], $this, ConnectEventParam::class);
            }
            catch(\Throwable $ex)
            {
                App::getBean('ErrorLog')->onException($ex);
            }
        });
        
        $server->on('receive', function(\swoole_server $server, $fd, $reactorID, $data){
            try{
                $this->trigger('receive', [
                    'server'    => $this,
                    'fd'        => $fd,
                    'reactorID' => $reactorID,
                    'data'      => $data,
                ], $this, ReceiveEventParam::class);
            }
            catch(\Throwable $ex)
            {
                App::getBean('ErrorLog')->onException($ex);
            }
        });
        
        $server->on('close', function(\swoole_server $server, $fd, $reactorID){
            try{
                $this->trigger('close', [
                    'server'    => $this,
                    'fd'        => $fd,
                    'reactorID' => $reactorID,
                ], $this, CloseEventParam::class);
            }
            catch(\Throwable $ex)
            {
                App::getBean('ErrorLog')->onException($ex);
            }
        });

        $server->on('BufferFull', function(\swoole_server $server, $fd){
            try{
                $this->trigger('bufferFull', [
                    'server'    => $this,
                    'fd'        => $fd,
                ], $this, BufferEventParam::class);
            }
            catch(\Throwable $ex)
            {
                App::getBean('ErrorLog')->onException($ex);
            }
        });

        $server->on('BufferEmpty', function(\swoole_server $server, $fd){
            try{
                $this->trigger('bufferEmpty', [
                    'server'    => $this,
                    'fd'        => $fd,
                ], $this, BufferEventParam::class);
            }
            catch(\Throwable $ex)
            {
                App::getBean('ErrorLog')->onException($ex);
            }
        });

    }

    /**
     * Get hprose Service
     *
     * @return  \Hprose\Swoole\Socket\Service
     */ 
    public function getHproseService()
    {
        return $this->hproseService;
    }
}