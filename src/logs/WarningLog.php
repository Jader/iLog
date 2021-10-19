<?php

namespace iLog\logs;

use yii\log\Logger;

/**
 * warning日志
 */
class WarningLog extends \iLog\logs\base\NormalLog
{
    /**
     * 过滤日志级别
     */
    protected function getLevel()
    {
        return Logger::LEVEL_WARNING;
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
