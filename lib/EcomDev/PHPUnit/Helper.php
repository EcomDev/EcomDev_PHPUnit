<?php

/**
 * Test Helpers Factory
 *
 */
class EcomDev_PHPUnit_Helper
{
    /**
     * Helpers container
     *
     * @var EcomDev_PHPUnit_HelperInterface[]
     */
    protected static $helpers = array();

    /**
     * Adds a new helper instance to helpers registry
     *
     * If $position is specified, it will use value
     * from before or after key as related helper
     *
     * @param EcomDev_PHPUnit_HelperInterface $helper
     * @param bool|array                       $position
     *
     * @throws RuntimeException
     */
    public static function add(EcomDev_PHPUnit_HelperInterface $helper, $position = false)
    {
        if ($position === false) {
            self::$helpers[] = $helper;
        } elseif (isset($position['after']) || isset($position['before'])) {
            $isBefore = isset($position['before']);
            $relatedHelper = $isBefore ? $position['before'] : $position['after'];

            if (is_string($relatedHelper)) {
                // Retrieving of helper by class name
                $relatedHelper = current(self::getHelpersByClass($relatedHelper));
            }

            $helperPosition = array_search($relatedHelper, self::$helpers, true);
            if ($helperPosition !== false) {
                array_splice(
                    self::$helpers,
                    $helperPosition + ($isBefore ? 0 : 1),
                    null,
                    array($helper)
                );
            }
        } else {
            throw new RuntimeException('Unknown position specified for helper addition');
        }
    }

    /**
     * Removes helper by instance
     *
     * @param EcomDev_PHPUnit_HelperInterface $helper
     */
    public static function remove(EcomDev_PHPUnit_HelperInterface $helper)
    {
        $helperPosition = array_search($helper, self::$helpers, true);
        if ($helperPosition !== false) {
            array_splice(self::$helpers, $helperPosition, 1);
        }
    }

    /**
     * Removes all helpers by class name from helpers array
     *
     * @param string $helperClass
     */
    public static function removeByClass($helperClass)
    {
        $helpersByClass = self::getHelpersByClass($helperClass);
        foreach ($helpersByClass as $helper) {
            self::remove($helper);
        }
    }

    /**
     * Returns helper by action,
     * if helper for action was not found it returns false
     *
     * @param $action
     * @return bool|EcomDev_PHPUnit_HelperInterface
     */
    public static function getByAction($action)
    {
        foreach (self::$helpers as $helper) {
            if ($helper->has($action)) {
                return $helper;
            }
        }

        return false;
    }

    /**
     * Checks existence of a helper for an action
     *
     * @param string $action
     * @return bool
     */
    public static function has($action)
    {
        return self::getByAction($action) !== false;
    }

    /**
     * Invokes a helper action with arguments as an array
     *
     * @param string $action
     * @param array  $args
     *
     * @throws RuntimeException
     * @return mixed
     */
    public static function invokeArgs($action, array $args)
    {
        $helper = self::getByAction($action);

        if (!$helper) {
            throw new RuntimeException(sprintf('Cannot find a helper for action "%s"', $action));
        }

        return $helper->invoke($action, $args);
    }

    /**
     * Invokes helper action with flat arguments
     *
     * @param string $action
     * @return mixed
     */
    public static function invoke($action /*, $arg1, $arg2, $arg3 ... $argN */)
    {
        $args = func_get_args();
        array_shift($args);
        return self::invokeArgs($action, $args);
    }

    /**
     * Sets test case to each helper instance
     *
     * @param PHPUnit_Framework_TestCase $testCase
     */
    public static function setTestCase(PHPUnit_Framework_TestCase $testCase)
    {
        foreach (self::$helpers as $helper) {
            $helper->setTestCase($testCase);
        }
    }

    /**
     * Calls setUp method on helper,
     * that implements EcomDev_PHPUnit_Helper_ListenerInterface
     *
     */
    public static function setUp()
    {
        foreach (self::$helpers as $helper) {
            if ($helper instanceof EcomDev_PHPUnit_Helper_ListenerInterface) {
                $helper->setUp();
            }
        }
    }

    /**
     * Calls tearDown method on helper,
     * that implements EcomDev_PHPUnit_Helper_ListenerInterface
     *
     */
    public static function tearDown()
    {
        foreach (self::$helpers as $helper) {
            if ($helper instanceof EcomDev_PHPUnit_Helper_ListenerInterface) {
                $helper->tearDown();
            }
        }
    }


    /**
     * Finds a helper instance by class name
     *
     * @param string $className
     * @return array
     */
    protected static function getHelpersByClass($className)
    {
        return array_filter(self::$helpers, function ($item) use ($className) {
            return get_class($item) === $className;
        });
    }

}