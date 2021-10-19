<?php

namespace iLog\logs;

use Yii;
use yii\base\InlineAction;
use iLog\logs\base\RequestLog;

class WebRequestLog extends \iLog\logs\base\RequestLog
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

        $headers = Yii::$app->getRequest()->getHeaders();
        $requestHeaders = [];
        foreach ($headers as $name => $value) {
            if (is_array($value) && count($value) == 1) {
                $requestHeaders[$name] = current($value);
            } else {
                $requestHeaders[$name] = $value;
            }
        }

        $responseHeaders = [];
        foreach (headers_list() as $header) {
            if (($pos = strpos($header, ':')) !== false) {
                $name = substr($header, 0, $pos);
                $value = trim(substr($header, $pos + 1));
                if (isset($responseHeaders[$name])) {
                    if (!is_array($responseHeaders[$name])) {
                        $responseHeaders[$name] = [$responseHeaders[$name], $value];
                    } else {
                        $responseHeaders[$name][] = $value;
                    }
                } else {
                    $responseHeaders[$name] = $value;
                }
            } else {
                $responseHeaders[] = $header;
            }
        }

        // 屏蔽post参数中的特定参数
        $postParams = empty($_POST) ? [] : $_POST;
        if (!empty($postParams && !empty($this->module->maskFields))) {
            foreach ($this->module->maskFields as $maskField) {
                if (!empty($postParams[$maskField])) {
                    $postParams[$maskField] = '***';
                }
            }
        }

        $contentType = Yii::$app->getRequest()->getContentType();
        $log = [
            'log_id' => $this->module->logId,
            'sub_log_id' => $this->module->subLogId,
            'parent_log_id' => $this->module->parentLogId,
            'time' => date('Y-m-d H:i:s', YII_BEGIN_TIME),
            'ts' => YII_BEGIN_TIME,
            'level' => $this->id,
            'group' => $this->getGroup(),
            'url' => sprintf("%s/%s", Yii::$app->getRequest()->getHostInfo(), Yii::$app->getRequest()->getPathInfo()),
            'query_string' => Yii::$app->getRequest()->getQueryString(),
            'ajax' => (int) Yii::$app->getRequest()->getIsAjax(),
            'method' => Yii::$app->getRequest()->getMethod(),
            'client_ip' => isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : \Yii::$app->getRequest()->getUserIP(),
            'statusCode' => Yii::$app->getResponse()->getStatusCode(),
            'duration' => (microtime(true) - YII_BEGIN_TIME) * 1000, //单位:ms
            'get' => empty($_GET) ? [] : $_GET,
            'post' => $postParams,
            'cookie' => empty($_COOKIE) ? [] : $_COOKIE,
            'cpu' => $this->getCpu(), // 进程cpu
            'memory' => memory_get_peak_usage(), // 内存峰值
            'requestHeaders' => $requestHeaders,
            'responseHeaders' => $responseHeaders,
            'requestBody' => [
                'Content Type' => $contentType,
                // content-type为multipart/form-data时不记录raw body，避免读入特殊字符导致日志写入有问题
                'Raw' => strpos(strtolower($contentType), 'multipart/form-data') !== false ? '' : Yii::$app->getRequest()->getRawBody()
            ],
            'userAgent' => \yii::$app->getRequest()->getUserAgent(),
            'referUrl' => \yii::$app->getRequest()->getReferrer()
        ];

        // 附加自定义请求参数
        if (!empty($this->module->customRequestParams)) {
            foreach ($this->module->customRequestParams as $param) {
                $log[$param] = $this->module->getCustomRequestParam($param);
            }
        }

        // 设置日志保存标识
        $this->isSaved = true;

        return [parent::addCustomFields($log)];
    }

    public function getGroup()
    {
        return 'request';
    }
}
