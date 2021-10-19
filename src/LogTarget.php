<?php

namespace iLog;

use yii\base\InvalidConfigException;
use yii\db\Exception;

/**
 * 输出为json字符串文件
 * @author ray-apple
 */
class LogTarget extends \yii\log\FileTarget
{
    /**
     * @var Module
     */
    public $module;
    
    public $enableRotation = true;
    
    protected $final = false;

    /**
     * @param \yii\debug\Module $module
     * @param array $config
     */
    public function __construct($module, $config = [])
    {
        $this->module = $module;
        $this->logFile = $this->module->logFile;
        $this->maxLogFiles = $this->module->maxLogFiles;
        $this->maxFileSize = $this->module->maxFileSize;
        $this->dirMode = $this->module->dirMode;
        $this->fileMode = $this->module->fileMode;
        $this->exportInterval = $this->module->exportInterval;
        parent::__construct($config);
    }
    
    public function init()
    {
        $logPath = dirname($this->logFile);
        if (!is_dir($logPath)) {
            try {
                \yii\helpers\FileHelper::createDirectory($logPath, $this->dirMode, true);
            } catch (\Exception $e) {
                if (strpos($e->getMessage(), 'Failed to create directory') !== false || strpos($e->getMessage(), 'Failed to change permissions for directory') !== false) {
                    throw $e;
                } else {
                    // 完善异常信息
                    throw new Exception("创建目录{$logPath}失败，错误消息：" . $e->getMessage());
                }
            }
        }
    }

    /**
     * 日志文件输出到单个文件中
     * @throws InvalidConfigException
     */
    private function writeToLogFile($messages)
    {
        // 按时间戳排序
        array_multisort(array_column($messages, 'ts'), SORT_ASC, $messages);
        $text = implode("\n", array_map([$this, 'formatMessage'], $messages)) . "\n";
        if (($fp = @fopen($this->logFile, 'a')) === false) {
            throw new InvalidConfigException("Unable to append to log file: {$this->logFile}");
        }
        @flock($fp, LOCK_EX);
        if ($this->enableRotation) {
            clearstatcache();
        }
        if ($this->enableRotation && @filesize($this->logFile) > $this->maxFileSize * 1024) {
            $this->rotateFiles();
            @flock($fp, LOCK_UN);
            @fclose($fp);
            @file_put_contents($this->logFile, $text, FILE_APPEND | LOCK_EX);
        } else {
            @fwrite($fp, $text);
            @flock($fp, LOCK_UN);
            @fclose($fp);
        }
        if ($this->fileMode !== null) {
            @chmod($this->logFile, $this->fileMode);
        }
    }
    
    /**
     * 日志文件输出到单个文件中
     * @throws InvalidConfigException
     */
    public function exportToSingleFile()
    {
        $messages = [];
        foreach ($this->module->levels as $log) {
            $messages = array_merge($messages, $log->save());
        }

        if (!empty($messages)) {
            $this->writeToLogFile($messages);
        }
    }

    /**
     * 日志文件根据级别输出到多个文件中
     * @throws InvalidConfigException
     */
    public function exportToMultiFile()
    {
        foreach ($this->module->levels as $level => $log) {
            $levelMessages = $log->save();
            if (!empty($levelMessages)) {
                $this->logFile = $this->getLevelLogFile($level);
                $this->writeToLogFile($levelMessages);
            }
        }
    }

    /**
     * 日志收集
     * @throws InvalidConfigException
     */
    public function collect($messages, $final)
    {
        $this->messages = array_merge($this->messages, $messages);
        $count = count($this->messages);
        if ($count > 0 && ($final || $this->exportInterval > 0 && $count >= $this->exportInterval)) {
            // set exportInterval to 0 to avoid triggering export again while exporting
            $oldExportInterval = $this->exportInterval;
            $this->exportInterval = 0;
            // 在一次请求中日志条数超过缓存条目时,会导致多次export造成多条request日志
            $this->final = $final;
            if ($this->module->isFileSeparated) {
                $this->exportToMultiFile();
            } else {
                $this->exportToSingleFile();
            }
            $this->exportInterval = $oldExportInterval;
            $this->messages = [];
        }
    }

    /**
     * 获取不同日志级别的日志输出文件
     * @throws InvalidConfigException
     */
    private function getLevelLogFile($level)
    {
        $logPath = dirname($this->logFile);
        return "{$logPath}/ilog_{$level}.log";
    }

    /**
     * 格式化消息
     * @return string
     */
    public function formatMessage($message)
    {
        return json_encode($message, JSON_UNESCAPED_UNICODE);
    }
}
