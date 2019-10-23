<?php

namespace RumTest;

use Swoole\Coroutine\Channel;

/**
 * 模拟客户端发送http请求
 * 配合测试
 * 使用协程
 */
class HttpClient
{
    /**
     * 发送get请求
     * @param {string} $host IP地址
     * @param {string} $port 端口
     * @param {string} $path 请求PATH
     * @param {array} $headers 头s
     * @param {array} $params 参数s
     * @param {Swoole\Coroutine\Channel} $chan 通道
     * @return void
     */
    public static function get($host, $port, $path, $headers, $params, $chan)
    {
        go(function () use ($chan, $host, $port, $path, $headers, $params) {
            $cli = new \Swoole\Coroutine\Http\Client($host, $port);
            $cli->setHeaders($headers);
            $cli->setData(http_build_query($params));
            $cli->get($path);
            $chan->push([
                'header' => $cli->headers,
                'body' => $cli->body,
                'cookie' => $cli->cookies
            ]);
        });
    }

    /**
     * 发送post请求
     * @param {string} $host IP地址
     * @param {string} $port 端口
     * @param {string} $path 请求PATH
     * @param {array} $headers 头s
     * @param {array} $data 发送的请求内容
     * @param {Swoole\Coroutine\Channel} $chan 通道
     * @return void
     */
    public static function post($host, $port, $path, $headers, $data, $chan)
    {
        go(function () use ($chan, $host, $port, $path, $headers, $data) {
            $cli = new \Swoole\Coroutine\Http\Client($host, $port);
            $cli->setHeaders($headers);
            $cli->post($path, $data);
            $chan->push([
                'header' => $cli->headers,
                'body' => $cli->body,
                'cookie' => $cli->cookies
            ]);
        });
    }
}
