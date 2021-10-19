<?php

namespace iLog\logs;

use yii\log\Logger;

/**
 * trace
 */
class TraceLog extends \iLog\logs\base\NormalLog
{
    /**
     * 过滤日志级别
     */
    protected function getLevel()
    {
        return Logger::LEVEL_TRACE;
    }
    
    /**
     * 排除类别
     */
    protected function getExcept()
    {
        return [];
    }
    
    public function getGroup()
    {
        return 'info';
    }
}
