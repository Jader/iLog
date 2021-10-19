<?php

namespace iLog\logs;

use Yii;

/**
 * 记录控制台请求
 *
 * @author ray-apple
 */
class ConsoleRequestLog extends \iLog\logs\base\RequestLog
{
    /**
     * @var boolean 日志是否已经保存
     */
    private $isSaved = false;

    /**
     * Saves data to be later used in debugger detail view.
     * This method is called on every page where debugger is enabled.
     *
     * @return mixed data to be saved
     */
    public function save()
    {
        if ($this->isSaved || empty($this->module->logId)) {
            return [];
        }

        $log = [
            'log_id' => $this->module->logId,
            'sub_log_id' => $this->module->subLogId,
            'parent_log_id' => $this->module->parentLogId,
            'time' => date('Y-m-d H:i:s', YII_BEGIN_TIME),
            'ts' => YII_BEGIN_TIME,
            'level' => $this->id,
            'group' => $this->getGroup(),
            'command' => Yii::$app->getRequest()->getScriptFile(),
            'params' => json_encode(Yii::$app->getRequest()->getParams(), JSON_UNESCAPED_UNICODE),
            'statusCode' => Yii::$app->getResponse()->exitStatus,
            'duration' => (microtime(true) - YII_BEGIN_TIME) * 1000, // 单位:毫秒
            'cpu' => $this->getCpu(), // 进程cpu
            'memory' => memory_get_peak_usage(), // 内存峰值
        ];

        // 设置日志保存标识
        $this->isSaved = true;

        return [parent::addCustomFields($log)];
    }

    /**
     * 设置日志所属组名
     */
    public function getGroup()
    {
        return 'request';
    }
}
