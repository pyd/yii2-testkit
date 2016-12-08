<?php
namespace pyd\testkit\tests\functional\base;


/**
 *
 *
 * @author pyd <pierre.yves.delettre@gmail.com>
 */
class LocationsTest extends \PHPUnit_Framework_TestCase
{
    protected function getTrait()
    {
        return $this->getObjectForTrait('pyd\testkit\functional\base\Locations');
    }

    /**
     * addLocation method add location correctly.
     */
    public function test_addLocation_locationAddedCorrectly()
    {
        $trait = $this->getTrait();
        $trait->addLocation('logoutBtn', ['css selector', '#user .logout'], false);
        $this->assertArrayHasKey('logoutBtn', $trait->getLocations());
        $this->assertEquals(['css selector', '#user .logout'], $trait->getLocations()['logoutBtn']);
    }

    /**
     * addLocation thrown ewception if $location argument is not valid
     *
     * @expectedException \yii\base\InvalidParamException
     * @dataProvider invalidLocationArrayDataProvider
     */
    public function test_addLocation_locationArgumentMustBeValid($location)
    {
        $trait = $this->getTrait();
        $trait->addLocation('alias', $location);
    }

    /**
     * @return array invalid locations
     */
    public static function invalidLocationArrayDataProvider()
    {
        return [
            [['invalid_strategy', 'valid_string_value']],
            [['name', ['array_instead_of_string_for_value']]],
            [['id', '']],
            [['', 'value-is-ok-but-not-strategy']],
        ];
    }

    /**
     * addLocation can overwrite existing location if $overwrite argument is set to true
     */
    public function test_addLocation_locationOverwritingAllowedIfOverwriteArgumentIsTrue()
    {
        $trait = $this->getTrait();
        $trait->addLocation('loginForm', ['tag name', 'form'], false);
        $trait->addLocation('loginForm', ['id', 'login-form'], true);
        $this->assertEquals($trait->getLocation('loginForm'), ['id', 'login-form']);
    }

    /**
     * addlocation cannot overwrite existing location if $overwrite argument is
     * set to false
     *
     * @expectedException \yii\base\InvalidParamException
     * @expectedExceptionMessage overwrite argument is set to false
     */
    public function test_addLocation_locationOverwritingExceptionIfOverwriteArgumentIsFalse()
    {
        $trait = $this->getTrait();
        $trait->addLocation('loginForm', ['tag name', 'form'], false);
        $trait->addLocation('loginForm', ['id', 'login-form'], false);
    }

    /**
     * createFinderParams can take a location alias as argument an return formated
     * location data
     */
    public function test_createFinderParams_withLocationAliasAsArgument()
    {
        $trait = $this->getTrait();
        $trait->addLocation('alias', ['id', 'element-id']);
        $params = $trait->createFinderParams('alias');
        $this->assertEquals(['using' => 'id', 'value' => 'element-id'], $params);
    }

    /**
     * createFinderParams can take a location array as argument an return formated
     * location data
     */
    public function test_createFinderParams_withLocationArrayAsArgument()
    {
        $trait = $this->getTrait();
        $params = $trait->createFinderParams(['tag name', 'textarea']);
        $this->assertEquals(['using' => 'tag name', 'value' => 'textarea'], $params);
    }


    /**
     * createFinderParam throws exception if $location argument is an unknown alias (a string)
     *
     * @expectedException \pyd\testkit\functional\base\UnknownLocationAliasException
     */
    public function test_createFinderParams_exceptionWithUnknownLocationAlias()
    {
        $trait = $this->getTrait();
        $trait->createFinderParams('unkown_alias');
    }

    /**
     * createFinderParams throws exception if $location argument is an invalid array
     *
     * @expectedException \yii\base\InvalidParamException
     * @dataProvider invalidLocationArrayDataProvider
     */
    public function test_createFinderParams_exceptionWithInvalidLocationArray($locationAsArray)
    {
        $trait = $this->getTrait();
        $trait->createFinderParams($locationAsArray);
    }
}
