<?php

namespace RumTest;

use Swoole\Coroutine\Channel;

/**
 * WaitGroup
 * 协程辅助工具
 */
class WaitGroup
{
    private $count; // 当前等待的协程个数
    private $chan;  // 通道

    /**
     * 协程辅助工具
     */
    public function __construct()
    {
        $this->count = 0;
        $this->chan = new Channel();
    }
    /**
     * 添加一个协程等待状态
     * @return void
     */
    public function add()
    {
        $this->count++;
    }
    /**
     * 标志一个协程已经完成
     * @return void
     */
    public function done()
    {
        $this->chan->push(true);
    }
    /**
     * 等待所有协程完成
     * @return void
     */
    public function wait()
    {
        while ($this->count--) {
            $this->chan->pop();
        }
    }
}
