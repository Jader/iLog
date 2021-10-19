<?php

namespace iLog;

use Yii;
use yii\base\Application;
use yii\base\BootstrapInterface;

/**
 * 依据yii-debug改写的log-hub,目的是把需要记录的信息以统一的方式记录到单个文件中
 * 并支持文件翻转
 */
class Component extends \yii\base\Component implements BootstrapInterface
{
    /**
     * @var LogTarget
     */
    public $logTarget;
    /**
     * @var array|levels[] list of log levels
     */
    public $levels = [];
    /**
     * @var array list of log category
     */
    public $categories = [];
    /**
     * @var boolean 不同级别的日志文件是否隔离
     */
    public $isFileSeparated = false;
    /**
     * @var string 定义输出文件
     */
    public $logFile = '@runtime/ilog.log';
    /**
     * @var string 输出类
     */
    public $targetClass = 'iLog\LogTarget';
    /**
     * @var integer the permission to be set for newly created debugger data files.
     * This value will be used by PHP [[chmod()]] function. No umask will be applied.
     * If not set, the permission will be determined by the current environment.
     * @since 2.0.6
     */
    public $fileMode;
    /**
     * @var integer the permission to be set for newly created directories.
     * This value will be used by PHP [[chmod()]] function. No umask will be applied.
     * Defaults to 0775, meaning the directory is read-writable by owner and group,
     * but read-only for other users.
     * @since 2.0.6
     */
    public $dirMode = 0775;
    /**
    * @var integer number of log files used for rotation. Defaults to 10.
    */
    public $maxLogFiles = 10;
    /**
     * @var integer maximum log file size(kb), in kilo-bytes. Defaults to 10240, meaning 10MB.
     */
    public $maxFileSize = 10240;
    /**
     * @var string 定义请求头中的logid标识,若未标识默认request_id
     */
    public $logIdFlag = 'XLogId';
    /**
     * @var string 定义请求参数中的sub-logid标识,若未标识默认XSubLogId
     */
    public $subLogIdFlag = 'XSubLogId';
    /**
     * @var string 日志穿透id,取上述标识值,未为空则自动生成一个唯一的id
     */
    public $logId = '';
    /**
     * @var string 父级请求的日志id,不存在则为空
     */
    public $parentLogId = '';
    /**
     * @var string 当前请求的日志id,自动生产一个唯一的id
     */
    public $subLogId = '';
    /**
     * @var int 日志缓冲量
     */
    public $exportInterval = 100;

    /**
     * @var array 读取日志自定义参数的类名
     */
    public $customFieldProviderClass = null;

    /**
     * @var \iLog\base\CustomFieldProviderBase 读取日志自定义参数的类
     */
    public $customFieldProvider = null;
    
    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $this->logFile = Yii::getAlias($this->logFile);

        if (!empty($this->customFieldProviderClass)) {
            $this->customFieldProvider = \Yii::createObject($this->customFieldProviderClass);
        }

        $this->initLogs();
    }

    /**
     * Initializes panels.
     */
    protected function initLogs()
    {
        $logs = [];
        // 默认取所有类型日志
        $coreLevels = $this->coreLevels();
        if (empty($this->levels)) {
            $logs = $coreLevels;
        } else {
            foreach ($this->levels as $id) {
                if (array_key_exists($id, $coreLevels)) {
                    $logs[$id] = $coreLevels[$id];
                }
            }
        }
        $this->levels = $logs;
        foreach ($this->levels as $id => $config) {
            if (is_string($config)) {
                $config = ['class' => $config];
            }
            $config['module'] = $this;
            $config['id'] = $id;
            $this->levels[$id] = Yii::createObject($config);
        }
    }

    /**
     * @inheritdoc
     */
    public function bootstrap($app)
    {
        //$this->logTarget = Yii::$app->getLog()->targets['iLog'] = new LogTarget($this);
        $this->logTarget = Yii::$app->getLog()->targets['iLog'] = Yii::createObject($this->targetClass, [$this]);
        // delay attaching event handler to the view component after it is fully configured
        $app->on(Application::EVENT_BEFORE_REQUEST, function () use ($app) {
            // 获取请求中的logid，logid为空时生成logid
            list($route, $params) = $app->getRequest()->resolve();
            $this->logId = !empty($params[$this->logIdFlag]) ? $params[$this->logIdFlag] : 'ilog_' . uniqid();

            // 获取父请求的logid
            $this->parentLogId = !empty($params[$this->subLogIdFlag]) ? $params[$this->subLogIdFlag] : '';

            // 设置当前请求的logid
            $this->subLogId = 'ilog_' . uniqid();
        });
    }

    /**
     * @return array default set of panels
     */
    protected function coreLevels()
    {
        $levels = [
            'config' => ['class' => 'iLog\logs\ConfigLog'],
            'request' => ['class' => 'iLog\logs\ConsoleRequestLog'],
            'database' => ['class' => 'iLog\logs\DatabaseLog'],
            'info' => ['class' => 'iLog\logs\InfoLog'],
            'error' => ['class' => 'iLog\logs\ErrorLog'],
            'warning' => ['class' => 'iLog\logs\WarningLog'],
            'trace' => ['class' => 'iLog\logs\TraceLog'],
            'profile' => ['class' => 'iLog\logs\ProfileLog'],
        ];

        return $levels;
    }
}
