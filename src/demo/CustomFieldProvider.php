<?php

namespace iLog;

use iLog\base\CustomFieldProviderBase;

/**
 * Class CustomFieldBase 读取日志自定义参数的基类（demo）
 * @package iLog
 */
class CustomFieldProvider extends CustomFieldProviderBase
{
    /**
     * 获取config类型日志的自定义字段
     * @return array
     */
    public function getConfigCustomField()
    {
        return ['自定义字段key' => '自定义字段value'];
    }

    /**
     * 获取request类型日志的自定义字段
     * @return array
     */
    public function getRequestCustomField()
    {
        return ['自定义字段key' => '自定义字段value'];
    }

    /**
     * 获取database类型日志的自定义字段
     * @return array
     */
    public function getDatabaseCustomField()
    {
        return ['自定义字段key' => '自定义字段value'];
    }

    /**
     * 获取error类型日志的自定义字段
     * @return array
     */
    public function getErrorCustomField()
    {
        return ['自定义字段key' => '自定义字段value'];
    }

    /**
     * 获取info类型日志的自定义字段
     * @return array
     */
    public function getInfoCustomField()
    {
        return ['自定义字段key' => '自定义字段value'];
    }

    /**
     * 获取trace类型日志的自定义字段
     * @return array
     */
    public function getTraceCustomField()
    {
        return ['自定义字段key' => '自定义字段value'];
    }

    /**
     * 获取warning类型日志的自定义字段
     * @return array
     */
    public function getWarningCustomField()
    {
        return ['自定义字段key' => '自定义字段value'];
    }

    /**
     * 获取profile类型日志的自定义字段
     * @return array
     */
    public function getProfileCustomField()
    {
        return ['自定义字段key' => '自定义字段value'];
    }
}
