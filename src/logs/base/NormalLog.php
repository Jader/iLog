<?php

namespace iLog\logs\base;

use yii\helpers\VarDumper;

abstract class NormalLog extends \iLog\logs\base\Log
{
    /**
     * 过滤日志级别
     */
    abstract protected function getLevel();
    
    /**
     * 排除类别
     */
    abstract protected function getExcept();
    

    /**
     * @inheritdoc
     */
    public function save()
    {
        $target = $this->module->logTarget;
        $messages = $target->filterMessages($target->messages, $this->getLevel(), $this->getCategories(), $this->getExcept());
        foreach ($messages as &$message) {
            if (!is_string($message[0])) {
                // exceptions may not be serializable if in the call stack somewhere is a Closure
                if ($message[0] instanceof \Throwable || $message[0] instanceof \Exception) {
                    $message[0] = (string) $message[0];
                } else {
                    $message[0] = VarDumper::export($message[0]);
                }
            }
        }
        
        return array_values(array_map([$this, 'formatMessage'], $messages));
    }
    
    protected function formatMessage($message)
    {
        $log = [
            'log_id' => $this->module->logId,
            'sub_log_id' => $this->module->subLogId,
            'parent_log_id' => $this->module->subLogId,
            'time' => date('Y-m-d H:i:s', $message[3]),
            'ts' => $message[3],
            'level' => $this->id,
            'group' => $this->getGroup(),
            'message' => $message[0]
        ];

        // info和error类型的日志需要记录category信息
        if (in_array($this->id, ['info', 'error'])) {
            $log['category'] = $message[2];
        }

        // error类型的日志需要记录url信息
        if ($this->id == 'error') {
            $log['url'] = \Yii::$app->getRequest()->isConsoleRequest ? json_encode(\Yii::$app->getRequest()->getParams(), JSON_UNESCAPED_UNICODE) : \Yii::$app->getRequest()->getHostInfo() .'/' . \Yii::$app->getRequest()->getPathInfo();
        }

        return parent::addCustomFields($log);
    }
}
