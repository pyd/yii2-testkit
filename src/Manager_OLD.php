<?php
namespace pyd\testkit;

use pyd\testkit\Events;

/**
 * Tests manager.
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class Manager extends \yii\base\Object
{
    /**
     * @var \pyd\testkit\fixtures\Manager
     */
    protected $fixtures;
    /**
     * @var \pyd\testkit\Events
     */
    protected $events;
    /**
     * @var \pyd\testkit\FileSharedData
     */
    protected $sharedData;
    /**
     * @var \pyd\testkit\web\DriverManager
     */
    protected $webDriverManager = '\pyd\testkit\web\DriverManager';
    /**
     * @var boolean this instance was created in a separate php process vs this
     * instance was created at the very begining of the tests execution. If the
     * latter, the 'setUpBeforeClass' and 'tearDownAfterClass' events occur
     * respectively at the beginig and at the end of each test case.
     */
    protected $isInIsolation;

    public function init()
    {
        $properties = ['fixtures', 'events', 'sharedData'];
        foreach ($properties as $property) {
            if (null === $this->$property){
                throw new \yii\base\InvalidConfigException("Property " . get_class() . "::$property must be initialized.");
            }
        }
        $this->isInIsolation = $this->sharedData->testCaseIsStarted();
        $this->registerObservers();
    }

    /**
     * @return \pyd\testkit\fixtures\Manager
     */
    public function getFixtures()
    {
        return $this->fixtures;
    }

    /**
     * @return \pyd\testkit\Events
     */
    public function getEvents()
    {
        return $this->events;
    }

    /**
     * @return \pyd\testkit\SharedData
     */
    public function getSharedData()
    {
        return $this->sharedData;
    }

    /**
     * @return \pyd\testkit\web\DriverManager
     */
    public function getWebDriverManager()
    {
        if (!is_object($this->webDriverManager)) {
            $this->webDriverManager = \Yii::createObject($this->webDriverManager);
        }
        return $this->webDriverManager;
    }

    /**
     * @see $isInIsolation
     * @return boolean
     */
    public function getIsInIsolation()
    {
        return $this->isInIsolation;
    }

    /**
     * Handle 'setUpBeforeClass' event.
     *
     * @param string $testCaseClassName
     * @param boolean $testCaseStart if true this event occurs at the beginning
     * of the test case. if false it occurs before a test method in isolation.
     */
    public function onSetUpBeforeClass($testCaseClassName, $testCaseStart)
    {
        if ($testCaseStart) {
            $this->sharedData->recordTestCaseStarted();
        }
    }

    /**
     * Handle 'tearDownAfterClass' event.
     *
     * @param string $testCaseClassName
     * @param boolean $testCaseEnd if true this event occurs at the end
     * of the test case. if false it occurs after a test method in isolation.
     */
    public function onTearDownAfterClass($testCaseClassName, $testCaseEnd)
    {
        if ($testCaseEnd) {
            $this->sharedData->destroy();
        }
    }

    /**
     * @see $fixtures
     * @param array $config
     */
    protected function setFixtures(array $config)
    {
        $this->fixtures = \Yii::createObject($config);
    }

    /**
     * @see $events
     * @param array $config
     */
    protected function setEvents(array $config)
    {
        $config['testkit'] = $this;
        $this->events = \Yii::createObject($config);
    }

    /**
     * @see $sharedData
     * @param array $config
     */
    protected function setSharedData(array $config)
    {
        $this->sharedData = \Yii::createObject($config);
    }

    /**
     * @param string|array $config
     */
    protected function setWebDriverManager($config)
    {
        $this->webDriverManager = $config;
    }

    /**
     * Set events observers.
     *
     * Order matters.
     */
    protected function registerObservers()
    {
        /** @var \pyd\testkit\fixtures\App */
        $fixtureApp = $this->fixtures->getApp();
        /** @var \pyd\testkit\fixtures\Db */
        $fixtureDb = $this->fixtures->getDb();

        $this->events->registerObservers(
            Events::SETUPBEFORECLASS,
            [
                $this,
                $fixtureApp->getConfigProvider(),
                $fixtureApp,
                $fixtureDb,
            ]);

        $this->events->registerObservers(
            Events::SETUP,
            [
                $fixtureDb,
            ]);

        $this->events->registerObservers(
            Events::TEARDOWN,
            [
                $fixtureDb,
                $fixtureApp,
            ]);

        $this->events->registerObservers(
            Events::TEARDOWNAFTERCLASS,
            [
                $fixtureDb,
                $fixtureApp,
                $this,
            ]);
    }
}
