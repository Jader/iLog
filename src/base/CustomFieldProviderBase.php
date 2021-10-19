<?php

namespace iLog\base;

/**
 * Class CustomFieldBase 读取日志自定义参数的基类
 * @package iLog\base
 */
class CustomFieldProviderBase
{
    /**
     * 获取config类型日志的自定义字段
     * @return array
     */
    public function getConfigCustomField()
    {
        return [];
    }

    /**
     * 获取request类型日志的自定义字段
     * @return array
     */
    public function getRequestCustomField()
    {
        return [];
    }

    /**
     * 获取database类型日志的自定义字段
     * @return array
     */
    public function getDatabaseCustomField()
    {
        return [];
    }

    /**
     * 获取error类型日志的自定义字段
     * @return array
     */
    public function getErrorCustomField()
    {
        return [];
    }

    /**
     * 获取info类型日志的自定义字段
     * @return array
     */
    public function getInfoCustomField()
    {
        return [];
    }

    /**
     * 获取trace类型日志的自定义字段
     * @return array
     */
    public function getTraceCustomField()
    {
        return [];
    }

    /**
     * 获取warning类型日志的自定义字段
     * @return array
     */
    public function getWarningCustomField()
    {
        return [];
    }

    /**
     * 获取profile类型日志的自定义字段
     * @return array
     */
    public function getProfileCustomField()
    {
        return [];
    }
}
