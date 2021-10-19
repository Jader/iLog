<?php


namespace iLog;

use Yii;
use yii\base\Application;
use yii\base\BootstrapInterface;
use yii\web\Response;

/**
 * 依据yii-debug改写的log-hub,目的是把需要记录的信息以统一的方式记录到单个文件中
 * 并支持文件翻转
 */
class Module extends \yii\base\Module implements BootstrapInterface
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
     * @var string 定义请求头中的logid标识,若未标识默认x-logid
     */
    public $logIdFlag = 'x-logid';
    /**
     * @var string 定义请求头中的sub-logid标识,若未标识默认x-sublogid
     */
    public $subLogIdFlag = 'x-sublogid';
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
     * @var array 自定义request需要记录的变量
     */
    public $customRequestParams = [];

    /**
     * @var array 读取日志自定义参数的类名
     */
    public $customFieldProviderClass = null;

    /**
     * @var \iLog\base\CustomFieldProviderBase 读取日志自定义参数的类
     */
    public $customFieldProvider = null;

    /**
     * @var array 屏蔽的字段
     */
    public $maskFields = [];

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
            // 获取请求中的logid
            $logId = $this->getRequestLogId($this->logIdFlag);
            // logid为空时生成logid
            $this->logId = $logId ? $logId : 'ilog_' . uniqid();

            // 获取父请求的logid
            $this->parentLogId = $this->getRequestLogId($this->subLogIdFlag);

            // 设置当前请求的logid
            $this->subLogId = 'ilog_' . uniqid();

            // 设置响应头部
            $app->getResponse()->on(Response::EVENT_AFTER_PREPARE, [$this, 'setResponseHeaders']);
        });
    }

    /**
     * 获取请求中的logid
     * @param $logIdFlag
     * @return string
     */
    private function getRequestLogId($logIdFlag)
    {
        $logId = '';
        // 从自定义参数中获取
        if (in_array($logIdFlag, $this->customRequestParams)) {
            $logId = $this->getCustomRequestParam($logIdFlag);
        } else {
            // 从header头中获取
            $logId = \Yii::$app->getRequest()->getHeaders()->get($logIdFlag, '');
        }

        return $logId;
    }

    /**
     * 获取request自定义的请求参数
     * @param $name
     * @return string
     */
    public function getCustomRequestParam($name)
    {
        // 优先从get参数中获取
        $urlParam = \Yii::$app->getRequest()->getQueryParam($name);
        if (!empty($urlParam)) {
            return $urlParam;
        }

        // 其次从header参数中获取
        $headerParam = \Yii::$app->getRequest()->getHeaders()->get($name);
        if (!empty($headerParam)) {
            return $headerParam;
        }

        // logId从url和header中获取，不从cookie获取
        if (in_array($name, [$this->logIdFlag, $this->subLogIdFlag])) {
            return '';
        }

        // 最后从cookie中取
        $cookieParam = \Yii::$app->getRequest()->getCookies()->getValue($name);
        return !empty($cookieParam) ? $cookieParam : '';
    }

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
    }

    /**
     * Setting headers to transfer debug data in AJAX requests
     * without interfering with the request itself.
     *
     * @param \yii\base\Event $event
     * @since 2.0.7
     */
    public function setResponseHeaders($event)
    {
        // 响应头加入请求id和耗时监控(微秒)
        $event->sender->getHeaders()
            ->set($this->logIdFlag, $this->logId);
    }

    /**
     * Resets potentially incompatible global settings done in app config.
     */
    protected function resetGlobalSettings()
    {
        Yii::$app->assetManager->bundles = [];
    }

    /**
     * @return array default set of levels
     */
    protected function coreLevels()
    {
        $levels = [
            'config' => ['class' => 'iLog\logs\ConfigLog'],
            'request' => ['class' => 'iLog\logs\WebRequestLog'],
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
