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
     * @var \pyd\testkit\SharedData
     */
    protected $sharedData;
    /**
     * @var boolean this instance was created in a separate php process vs this
     * instance was created at the very begining of the tests execution. If the
     * latter, the 'setUpBeforeClass' and 'tearDownAfterClass' events occur
     * respectively at the beginig and at the end of each test case.
     */
    protected $isInIsolation;

    /**
     *
     */
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
     * @see $isInIsolation
     * @return boolean
     */
    public function getIsInIsolation()
    {
        return $this->isInIsolation;
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
