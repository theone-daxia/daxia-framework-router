<?php

namespace Router;

/**
 * @method static get(string $route, Callable $callback)
 * @method static post(string $route, Callable $callback)
 * @method static put(string $route, Callable $callback)
 * @method static delete(string $route, Callable $callback)
 * @method static options(string $route, Callable $callback)
 * @method static head(string $route, Callable $callback)
 */
class Router
{
    public static $routes = array();
    public static $methods = array();
    public static $callbacks = array();
    public static $patterns = array();
    public static $error_callback;

    /**
     * 调用不存在的静态方法时调用的方法
     *
     * @param  string $method 方法名
     * @param  mix $params 参数
     *
     * @return void
     */
    public static function __callstatic($method, $params)
    {
        $uri = strpos($params[0], '/') === 0 ? $params[0] : '/' . $params[0];
        $callback = $params[1];

        array_push(self::$routes, $uri);
        array_push(self::$methods, strtoupper($method));
        array_push(self::$callbacks, $callback);
    }

    public static function dispatch()
    {
        $found_router = false;
        self::$routes = preg_replace('/\/+/', '/', self::$routes);
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'];

        if (in_array($uri, self::$routes)) {

            $route_pos = array_keys(self::$routes, $uri);

            foreach ($route_pos as $key => $pos) {

                if (self::$methods[$pos] == $method || self::$method[$pos] == 'ANY') {

                    $found_router = true;
                    self::foundRouterHandle($pos);
                }
            }
        }

        if ($found_router == false) {

            self::notFoundRouterHandle();
        }
    }

    /**
     * 找到路由后的处理
     *
     * @param  int $pos 位置下标
     *
     * @return void
     */
    public static function foundRouterHandle($pos)
    {
        if (!is_object(self::$callbacks[$pos])) {

            $parts = explode('@', self::$callbacks[$pos]);
            $class = new $parts[0];
            $function = $parts[1];
            $class->{$parts[1]}();
        } else {
            call_user_func(self::$callbacks[$pos]);
        }
    }

    /**
     * 未找到路由后的处理
     *
     * @return void
     */
    public function notFoundRouterHandle()
    {
        if ( ! self::$error_callback) {

            self::$error_callback = function() {
                header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
                echo '404';
            };

        } else {

            if (is_string(self::$error_callback)) {

                self::get($_SERVER['REQUEST_URI'], self::$error_callback);
                self::$error_callback = null;
                self::dispatch();
                return;
            }
        }

        call_user_func(self::$error_callback);
    }
}
