<?php

namespace iLog\logs\base;

/**
 * Created by PhpStorm.
 * User: zhangqing
 * Date: 2019-12-27
 * Time: 15:43
 */
abstract class RequestLog extends \iLog\logs\base\Log
{
    /**
     * 获取请求时cpu
     */
    protected function getCpu()
    {
        try {
            // 兼容windows下不支持该函数/和用户自定义函数重名
            $cpu = getrusage();
            $cpu = $cpu['ru_utime.tv_usec'];
        } catch (\Exception $exception) {
            $cpu = 0;
        }

        return $cpu;
    }
}
