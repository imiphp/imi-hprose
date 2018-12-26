<?php
namespace Imi\Hprose\Listener;

use Imi\ServerManage;
use Imi\RequestContext;
use Imi\Event\EventParam;
use Imi\Event\IEventListener;
use Imi\Bean\Annotation\Listener;
use Imi\Server\Route\RouteCallable;
use Imi\Bean\Annotation\AnnotationManager;
use Imi\Hprose\Route\Annotation\HproseRoute;
use Imi\Hprose\Route\Annotation\HproseAction;
use Imi\Hprose\Route\Annotation\Parser\HproseControllerParser;

/**
 * Hprose 服务器路由初始化
 * @Listener("IMI.MAIN_SERVER.WORKER.START")
 */
class RouteInit implements IEventListener
{
    /**
     * 事件处理方法
     * @param EventParam $e
     * @return void
     */
    public function handle(EventParam $e)
    {
        $this->parseAnnotations($e);
    }

    /**
     * 处理注解路由
     * @return void
     */
    private function parseAnnotations(EventParam $e)
    {
        $controllerParser = HproseControllerParser::getInstance();
        foreach(ServerManage::getServers() as $name => $server)
        {
            if(!$server instanceof \Imi\Server\Hprose\Server)
            {
                continue;
            }
            RequestContext::create();
            RequestContext::set('server', $server);
            $route = $server->getBean('HproseRoute');
            foreach($controllerParser->getByServer($name) as $className => $classItem)
            {
                $classAnnotation = $classItem['annotation'];
                foreach(AnnotationManager::getMethodsAnnotations($className, HproseAction::class) as $methodName => $actionAnnotations)
                {
                    $routes = AnnotationManager::getMethodAnnotations($className, $methodName, HproseRoute::class);
                    if(!isset($routes[0]))
                    {
                        $routes = [
                            new HproseRoute([
                                'name' => $methodName,
                            ])
                        ];
                    }
                    
                    foreach($routes as $routeItem)
                    {
                        $route->addRuleAnnotation($routeItem, new RouteCallable($className, $methodName), [
                            'serverName'    =>  $name,
                            'controller'    =>  $classAnnotation,
                        ]);
                    }
                }
            }
            RequestContext::destroy();
        }
    }

}