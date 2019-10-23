<?php

namespace RumTest;

use Swoole;
use swoole_atomic;

/**
 * 前后台进程模拟
 * 代码从swoole源码中拷贝
 */
class ProcessManager
{
    /**
     * @var swoole_atomic
     */
    protected $atomic;

    /**
     * wait wakeup 1s default
     */
    protected $waitTimeout = 1.0;

    public $clientFunc; // 客户端
    public $serveFunc;  // 服务
    protected $servePid;

    /**
     * @var Swoole\Process
     */
    protected $serveProcess;

    /**
     * 测试进程模拟
     */
    public function __construct($client, $serve)
    {
        $this->atomic = new Swoole\Atomic(0);
        $this->clientFunc = $client;
        $this->serveFunc = $serve;
    }
    /**
     * 设置客户端进程默认等待启动时间
     */
    public function setWaitTimeout(int $value)
    {
        $this->waitTimeout = $value;
    }

    //等待信息
    public function wait()
    {
        return $this->atomic->wait($this->waitTimeout);
    }

    //唤醒等待的进程
    public function wakeup()
    {
        return $this->atomic->wakeup();
    }

    /**
     * 运行客户端节点
     */
    public function runClientFunc($pid = 0)
    {
        if (!$this->clientFunc) {
            return (function () {
                $this->kill();
            })();
        } else {
            return call_user_func($this->clientFunc, $pid);
        }
    }

    /**
     * 运行服务端进程
     */
    public function runServeFunc()
    {
        return call_user_func($this->serveFunc);
    }

    /**
     * 终结服务端进程
     * @param bool $force
     */
    public function kill(bool $force = false)
    {
        if (!defined('PCNTL_ESRCH')) {
            define('PCNTL_ESRCH', 3);
        }
        if ($this->servePid) {
            if ($force || (!@Swoole\Process::kill($this->servePid) && swoole_errno() !== PCNTL_ESRCH)) {
                if (!@Swoole\Process::kill($this->servePid, SIGKILL) && swoole_errno() !== PCNTL_ESRCH) {
                    exit('KILL SERVE PROCESS ERROR');
                }
            }
        }
    }
    /**
     * 运行
     */
    public function run($redirectStdout = false)
    {
        $this->serveProcess = new Swoole\Process(function () {
            $this->runServeFunc();
            exit;
        }, $redirectStdout, $redirectStdout);
        if (!$this->serveProcess || !$this->serveProcess->start()) {
            exit("ERROR: CAN NOT CREATE PROCESS\n");
        }
        register_shutdown_function(function () {
            $this->kill();
        });
        $this->wait();
        $this->runClientFunc($this->servePid = $this->serveProcess->pid);
        Swoole\Event::wait();
        return true;
    }

    public function getServeOutput()
    {
        $this->serveProcess->setBlocking(false);
        $output = '';
        while (1) {
            $data = @$this->serveProcess->read();
            if (!$data) {
                break;
            } else {
                $output .= $data;
            }
        }
        return $output;
    }
}
