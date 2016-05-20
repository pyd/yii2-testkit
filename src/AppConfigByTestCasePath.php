<?php
namespace pyd\testkit;

/**
 * @inheritdoc
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class AppConfigByTestCasePath extends \yii\base\Object implements interfaces\AppConfigInterface
{
    protected $config;

    public function getBootstrapFiles()
    {
        return[];
    }

    public function getServerVariables()
    {
        return [];
    }

    public function getYiiAppConfig()
    {
        return [];
    }

    public function setConfig($config)
    {
        if (is_array($config)) {
            $this->config = $config;
        } else {
            $this->config = require $config;
        }
    }

    protected function getConfigByPath()
    {

    }
}
