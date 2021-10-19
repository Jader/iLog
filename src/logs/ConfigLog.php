<?php

namespace iLog\logs;

/**
 * 记录php运行环境
 */

class ConfigLog extends \iLog\logs\base\Log
{
    /**
     * @var boolean 日志是否已经保存
     */
    private $isSaved = false;

    /**
     * @inheritdoc
     */
    public function save()
    {
        if ($this->isSaved || empty($this->module->logId)) {
            return [];
        }

        $log = [
            'log_id' => $this->module->logId,
            'sub_log_id' => $this->module->subLogId,
            'parent_log_id' => $this->module->subLogId,
            'time' => date('Y-m-d H:i:s', YII_BEGIN_TIME),
            'ts' => YII_BEGIN_TIME,
            'level' => $this->id,
            'group' => $this->getGroup(),
            'php-version' => PHP_VERSION
        ];

        // 设置日志保存标识
        $this->isSaved = true;

        return [parent::addCustomFields($log)];
    }
    
    public function getGroup()
    {
        return 'config';
    }
}
