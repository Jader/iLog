<?php

namespace iLog\logs;

use yii\log\Logger;

class DatabaseLog extends \iLog\logs\base\Log
{
    /**
     * @var array 缓存日志
     */
    private $logStack = [];

    /**
     * @var array 缓存最新的数据库连接
     */
    private $lastDbConnection = null;

    /**
     * @inheritdoc
     */
    public function init()
    {
    }

    /**
     * @inheritdoc
     */
    public function save()
    {
        $messages = $this->getProfileLogs();
        return  array_map([$this, 'formatMessage'], $messages);
    }

    /**
     * Returns all profile logs of the current request for this panel. It includes categories such as:
     * 'yii\db\Command::query', 'yii\db\Command::execute'.
     * @return array
     */
    public function getProfileLogs()
    {
        $target = $this->module->logTarget;
        $userCategories = $this->getCategories();
        $categories = $userCategories ?: ['yii\db\Command::query', 'yii\db\Command::execute', 'yii\db\Connection::open'];
        $messages = $target->filterMessages($target->messages, Logger::LEVEL_PROFILE, $categories);
        if (empty($messages)) {
            return [];
        }

        // 判断数据库日志记录的模式
        $messages = array_values($messages);
        $firstMessage = $messages[0];
        $sqlInfo = json_decode($firstMessage[0], true);
        if (!empty($sqlInfo['duration'])) {
            // 新模式：修改了底层的Command.php、Connection.php，sql执行时长、数据库连接信息已经获取
            $timings = $this->getDatabaseLogs($messages);
        } else {
            // 旧模式：未修改底层代码，从上下文中计算sql执行时长、获取数据库连接信息（可能存在错乱的情况）
            $timings = $this->calculateTimings($messages);
        }

        return $this->removeMessages($timings, 'info', '/^(SHOW).+?/');
    }

    /**
     * 组装sql执行时间、数据库连接信息
     * @param $messages
     * @return array
     */
    private function getDatabaseLogs($messages)
    {
        $timings = [];
        foreach ($messages as $message) {
            $sqlInfo = json_decode($message[0], true);
            $timings[] = [
                'info' => $sqlInfo['message'],
                'category' => $message[2],
                'timestamp' => $message[3],
                'trace' => $message[4],
                'duration' => $sqlInfo['duration'],
                'db' => $sqlInfo['db']
            ];
        }

        return $timings;
    }

    /**
     * 通过上下文获取sql执行时间、数据库连接信息
     * @param $messages
     * @return array
     */
    private function calculateTimings($messages)
    {
        $timings = [];
        foreach ($messages as $i => $log) {
            list($token, $level, $category, $timestamp) = $log;
            $log[5] = $i;
            if ($level == Logger::LEVEL_PROFILE_BEGIN) {
                $this->logStack[] = $log;
            } elseif ($level == Logger::LEVEL_PROFILE_END) {
                if ($category == 'yii\db\Connection::open') {
                    // 记录最新的数据库连接
                    $this->lastDbConnection = str_replace('Opening DB connection: ', '', $token);
                }

                if (($last = array_pop($this->logStack)) !== null && $last[0] === $token) {
                    $timings[$last[5]] = [
                        'info' => $last[0],
                        'category' => $last[2],
                        'timestamp' => $last[3],
                        'trace' => $last[4],
                        'duration' => $timestamp - $last[3],
                        'db' => $this->lastDbConnection
                    ];
                }
            }
        }

        ksort($timings);

        return array_values($timings);
    }

    protected function formatMessage($message)
    {
        $log = [
            'log_id' => $this->module->logId,
            'sub_log_id' => $this->module->subLogId,
            'parent_log_id' => $this->module->subLogId,
            'time' => date('Y-m-d H:i:s', $message['timestamp']),
            'ts' => $message['timestamp'],
            'level' => $this->id,
            'group' => $this->getGroup(),
            'message' => preg_replace('/\s+/', ' ', $message['info']),
            'duration' => $message['duration'] * 1000, //单位:ms
            'db' => $message['db'],
            'url' => \Yii::$app->getRequest()->isConsoleRequest ? json_encode(\Yii::$app->getRequest()->getParams(), JSON_UNESCAPED_UNICODE) : \Yii::$app->getRequest()->getHostInfo() .'/' . \Yii::$app->getRequest()->getPathInfo()
        ];
        
        return parent::addCustomFields($log);
    }

    public function getGroup()
    {
        return 'database';
    }
}
