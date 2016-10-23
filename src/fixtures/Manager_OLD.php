<?php
namespace pyd\testkit\fixtures;

use pyd\testkit\EventsDispatcher;

/**
 * Fixtures manager.
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class Manager extends \yii\base\Object
{
    /**
     * @var \pyd\testkit\fixtures\App
     */
    protected $fixtureApp;
    /**
     * @var \pyd\testkit\fixtures\Db
     */
    protected $fixtureDb;
    /**
     * @var \pyd\testkit\EventsDispatcher
     */
    protected $eventsDispatcher;
    /**
     * @var \pyd\testkit\SharedDataFile
     */
    protected $sharedData;
    /**
     * @var boolean this instance is executed in a separate process created
     * to run an isolated test method.
     */
    protected $isInIsolation;

    public function init()
    {
        foreach (['fixtureApp', 'fixtureDb', 'eventsDispatcher', 'sharedData'] as $property)
        {
            if (null === $this->$property) {
                throw new \yii\base\InvalidConfigException(get_class($this) . "::$property should be initialized.");
            }
        }

        $this->isInIsolation = $this->sharedData->testCaseIsStarted();

        $this->registerObservers();
    }

    public function getIsInIsolation()
    {
        return $this->isInIsolation;
    }

    /**
     * @return \pyd\testkit\fixtures\App
     * @see $fixturesApp
     */
    public function getFixtureApp()
    {
        return $this->fixtureApp;

    }

    /**
     * @return \pyd\testkit\fixtures\Db
     * @see $fixtureDb
     */
    public function getFixtureDb()
    {
        return $this->fixtureDb;
    }

    /**
     * @return \pyd\testkit\EventsDispatcher
     * @see $eventsDispatcher
     */
    public function getEventsDispatcher()
    {
        return $this->eventsDispatcher;
    }

    public function onSetUpBeforeClass($testCaseClassName, $testCaseStart)
    {
        if ($testCaseStart) {
            $this->sharedData->recordTestCaseStarted();
        }
    }

    public function onTearDownAfterClass($testCaseClassName, $testCaseEnd)
    {
        if ($testCaseEnd) {
            $this->sharedData->destroy();
        }
    }


    /**
     * @param \pyd\testkit\SharedData $sharedData
     */
    protected function setSharedData($sharedData)
    {
        $className = $sharedData['class'];
        $this->sharedData = new $className($sharedData['storageFile']);
    }
    /**
     * @return \pyd\testkit\SharedDataFile
     */
    public function getSharedData()
    {
        return $this->sharedData;
    }

    /**
     * Setter for @see $fixtureApp
     *
     * @param array $config
     */
    protected function setFixtureApp(array $config)
    {
        $this->fixtureApp = \Yii::createObject($config);
    }

    /**
     * Setter for @see $fixtureDb
     *
     * @param array $config
     */
    protected function setFixtureDb(array $config)
    {
        $this->fixtureDb = \Yii::createObject($config);
    }

    /**
     * Setter for @see $eventsDispatcher
     *
     * @param array $config
     */
    protected function setEventsDispatcher(array $config)
    {
        $config['fixturesManager'] = $this;
        $this->eventsDispatcher = \Yii::createObject($config);
    }

    /**
     * Set events observers.
     *
     * Order matters.
     */
    protected function registerObservers()
    {
        $this->eventsDispatcher->registerObservers(
            EventsDispatcher::EVENT_SETUPBEFORECLASS,
            [
                $this,
                $this->fixtureApp->getConfigProvider(),
                $this->fixtureApp,
                $this->fixtureDb
            ]);

        $this->eventsDispatcher->registerObservers(
            EventsDispatcher::EVENT_SETUP,
            [
                $this->fixtureDb,
            ]);

        $this->eventsDispatcher->registerObservers(
            EventsDispatcher::EVENT_TEARDOWN,
            [
                $this->fixtureDb,
                $this->fixtureApp
            ]);

        $this->eventsDispatcher->registerObservers(
            EventsDispatcher::EVENT_TEARDOWNAFTERCLASS,
            [
                $this->fixtureDb,
                $this->fixtureApp,
                $this
            ]);
    }
}
