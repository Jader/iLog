<?php

namespace iLog\logs\base;

abstract class Log
{
    /**
     * @var string panel unique identifier.
     * It is set automatically by the container module.
     * level-id
     */
    public $id;
    /**
     * @var string request data set identifier.
     */
    public $tag;
    /**
     * @var Module
     */
    public $module;
    /**
     * @var mixed data associated with panel
     */
    public $data;
    /**
     * @var array array of actions to add to the debug modules default controller.
     * This array will be merged with all other panels actions property.
     * See [[\yii\base\Controller::actions()]] for the format.
     */
    public $actions = [];

    /**
     * Saves data to be later used in debugger detail view.
     * This method is called on every page where debugger is enabled.
     *
     * @return mixed data to be saved
     */
    abstract public function save();

    /**
     * 设置日志所属组名
     */
    abstract public function getGroup();

    /**
     * 获取日志类别,即只收集该类别的日志,为空表示不过滤类别
     * @return array
     */
    public function getCategories()
    {
        return array_key_exists($this->id, $this->module->categories) ? $this->module->categories[$this->id] : [];
    }

    /**
     * 过滤掉指定消息
     * @param array $messages 消息集合
     * @key string|int $message中匹配字段key或index
     * @param string $pattern 要移除的消息正则
     * @return array
     */
    protected function removeMessages($messages, $key, $pattern)
    {
        return array_filter($messages, function ($msg) use ($pattern, $key) {
            return !preg_match($pattern, $msg[$key]);
        });
    }

    /**
     * 附加自定义字段
     * @param array $log
     * @return array
     */
    protected function addCustomFields($log)
    {
        // 根据日志类型拼接自定义字段
        if (!empty($this->module->customFieldProvider)) {
            $functionName = 'get' . ucfirst($this->id) . 'CustomField';
            $result = $this->module->customFieldProvider->{$functionName}();
            if (!empty($result)) {
                $log = array_merge($log, $result);
            }
        }

        return $log;
    }
}
