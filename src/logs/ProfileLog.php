<?php

namespace iLog\logs;

use yii\log\Logger;

/**
 * profile日志
 */
class ProfileLog extends \iLog\logs\base\NormalLog
{
    /**
     * 过滤日志级别
     */
    protected function getLevel()
    {
        return Logger::LEVEL_PROFILE;
    }
    
    /**
     * 排除类别
     */
    protected function getExcept()
    {
        return ['yii\db\Command::query', 'yii\db\Command::execute', 'yii\db\Connection::open'];
    }
    
    public function getGroup()
    {
        return 'info';
    }
}
