<?php
/**
 * PHP Unit test suite for Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   EcomDev
 * @package    EcomDev_PHPUnit
 * @copyright  Copyright (c) 2013 EcomDev BV (http://www.ecomdev.org)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author     Ivan Chepurnyi <ivan.chepurnyi@ecomdev.org>
 */

use EcomDev_Utils_Reflection as ReflectionUtil;
use PHPUnit\Framework\MockObject\Builder\InvocationMocker as BuilderInvocationMocker;
use PHPUnit\Framework\MockObject\CannotUseAddMethodsException;
use PHPUnit\Framework\MockObject\CannotUseOnlyMethodsException;
use PHPUnit\Framework\MockObject\ClassAlreadyExistsException;
use PHPUnit\Framework\MockObject\ClassIsFinalException;
use PHPUnit\Framework\MockObject\DuplicateMethodException;
use PHPUnit\Framework\MockObject\Generator;
use PHPUnit\Framework\MockObject\InvalidMethodNameException;
use PHPUnit\Framework\MockObject\InvocationHandler;
use PHPUnit\Framework\MockObject\MockBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\OriginalConstructorInvocationRequiredException;
use PHPUnit\Framework\MockObject\ReflectionException;
use PHPUnit\Framework\MockObject\Rule\InvocationOrder;
use PHPUnit\Framework\MockObject\RuntimeException;
use PHPUnit\Framework\MockObject\UnknownTypeException;

/**
 * PHPUnit Mock Object Proxy
 *
 * Used to support mock builder auto-apply as soon as expects method is called.
 * @method \PHPUnit\Framework\MockObject\Builder\InvocationMocker method($constraint)
 */
class EcomDev_PHPUnit_Mock_Proxy implements PHPUnit\Framework\MockObject\MockObject
{
    /**
     * @var TestCase
     */
    private $testCase;

    /**
     * @var string
     */
    private $type;

    /**
     * @var null|string[]
     */
    private $methods = [];

    /**
     * @var bool
     */
    private $emptyMethodsArray = false;

    /**
     * @var string
     */
    private $mockClassName = '';

    /**
     * @var array
     */
    private $constructorArgs = [];

    /**
     * @var bool
     */
    private $originalConstructor = true;

    /**
     * @var bool
     */
    private $originalClone = true;

    /**
     * @var bool
     */
    private $autoload = true;

    /**
     * @var bool
     */
    private $cloneArguments = false;

    /**
     * @var bool
     */
    private $callOriginalMethods = false;

    /**
     * @var ?object
     */
    private $proxyTarget;

    /**
     * @var bool
     */
    private $allowMockingUnknownTypes = true;

    /**
     * @var bool
     */
    private $returnValueGeneration = true;

    /**
     * @var Generator
     */
    private $generator;

    protected $mockInstance;

    /**
     * Original mocked class alias
     * 
     * @var string
     */
    protected $classAlias;

    /**
     * Added class alias as property
     * 
     * @param \PHPUnit\Framework\TestCase $testCase
     * @param array|string $type
     * @param null $classAlias
     */
    public function __construct(\PHPUnit\Framework\TestCase $testCase, $type, $classAlias = null)
    {
        $this->classAlias = $classAlias;
        $this->testCase  = $testCase;
        $this->type      = $type;
        $this->generator = new Generator;
    }

    /**
     * Adds method name to a mock builder
     *
     * @param string $methodName
     * @return $this
     */
    public function addMethod($methodName)
    {
        $methods = ReflectionUtil::getRestrictedPropertyValue($this, 'methods');
        $methods[] = $methodName;
        ReflectionUtil::setRestrictedPropertyValue($this, 'methods', $methods);
        return $this;
    }


    /**
     * Removes method name from a mock builder
     *
     * @param string $methodName
     * @return $this
     */
    public function removeMethod($methodName)
    {
        $methods = ReflectionUtil::getRestrictedPropertyValue($this, 'methods');
        $methodIndex = array_search($methodName, $methods);
        if ($methodIndex !== false) {
            array_splice($methods, $methodIndex, 1);
        }
        ReflectionUtil::setRestrictedPropertyValue($this, 'methods', $methods);
        return $this;
    }

    /**
     * Preserves methods from override in mocked object
     *
     * @return $this
     */
    public function preserveMethods()
    {
        $this->setMethods(null);
        return $this;
    }

    /**
     * Proxied mock instance retrieval
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    public function getMockInstance()
    {
        if ($this->mockInstance === null) {
            $reflection = ReflectionUtil::getReflection(
                ReflectionUtil::getRestrictedPropertyValue($this, 'type')
            );
            $this->mockInstance = ($reflection->isAbstract() || $reflection->isInterface())
                                    ? $this->getMockForAbstractClass() : $this->getMock();
        }

        return $this->mockInstance;
    }

    /**
     * Returns mock class name for generated mock instance
     *
     * @return string
     */
    public function getMockClass()
    {
        return get_class($this->getMockInstance());
    }


    public function expects(InvocationOrder $invocationRule): BuilderInvocationMocker
    {
        return $this->getMockInstance()->expects($invocationRule);
    }

    public function replaceByMock($type)
    {
        EcomDev_PHPUnit_Test_Case_Util::replaceByMock($type, $this->classAlias, $this);
        return $this;
    }

    public function __phpunit_getInvocationMocker()
    {
        throw new RuntimeException(
            'Mock object proxy cannot be used for retrieving invocation mockers, '
                . 'use getMockInstance method for real mock object'
        );
    }

    public function __phpunit_setOriginalObject($originalObject): void
    {
        throw new RuntimeException(
            'Mock object proxy cannot be used for setting original object, '
            . 'use getMockInstance method for real mock object'
        );
    }

    public function __phpunit_verify(bool $unsetInvocationMocker = true): void
    {
        throw new RuntimeException(
            'Mock object proxy cannot be used for verifying mock'
            . 'use getMockInstance method for real mock object'
        );
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array(
            array($this->getMockInstance(), $name),
            $arguments
        );
    }


    public function __phpunit_getInvocationHandler(): InvocationHandler
    {
        throw new RuntimeException(
            'Mock object proxy cannot be used for retrieving invocation handler'
            . 'use getMockInstance method for real mock object'
        );
    }

    public function __phpunit_hasMatchers(): bool
    {
        throw new RuntimeException(
            'Mock object proxy cannot be used for verifying mock'
            . 'use getMockInstance method for real mock object'
        );
    }

    public function __phpunit_setReturnValueGeneration(bool $returnValueGeneration): void
    {
        throw new RuntimeException(
            'Mock object proxy cannot be used for setting return value generation'
            . 'use getMockInstance method for real mock object'
        );
    }

    /**
     * Creates a mock object using a fluent interface.
     *
     * @throws \PHPUnit\Framework\InvalidArgumentException
     * @throws ClassAlreadyExistsException
     * @throws ClassIsFinalException
     * @throws DuplicateMethodException
     * @throws InvalidMethodNameException
     * @throws OriginalConstructorInvocationRequiredException
     * @throws ReflectionException
     * @throws RuntimeException
     * @throws UnknownTypeException
     *
     * @psalm-return MockObject&MockedType
     */
    public function getMock(): MockObject
    {
        $object = $this->generator->getMock(
            $this->type,
            !$this->emptyMethodsArray ? $this->methods : null,
            $this->constructorArgs,
            $this->mockClassName,
            $this->originalConstructor,
            $this->originalClone,
            $this->autoload,
            $this->cloneArguments,
            $this->callOriginalMethods,
            $this->proxyTarget,
            $this->allowMockingUnknownTypes,
            $this->returnValueGeneration
        );

        $this->testCase->registerMockObject($object);

        return $object;
    }

    /**
     * Creates a mock object for an abstract class using a fluent interface.
     *
     * @psalm-return MockObject&MockedType
     *
     * @throws \PHPUnit\Framework\Exception
     * @throws ReflectionException
     * @throws RuntimeException
     */
    public function getMockForAbstractClass(): MockObject
    {
        $object = $this->generator->getMockForAbstractClass(
            $this->type,
            $this->constructorArgs,
            $this->mockClassName,
            $this->originalConstructor,
            $this->originalClone,
            $this->autoload,
            $this->methods,
            $this->cloneArguments
        );

        $this->testCase->registerMockObject($object);

        return $object;
    }

    /**
     * Creates a mock object for a trait using a fluent interface.
     *
     * @psalm-return MockObject&MockedType
     *
     * @throws \PHPUnit\Framework\Exception
     * @throws ReflectionException
     * @throws RuntimeException
     */
    public function getMockForTrait(): MockObject
    {
        $object = $this->generator->getMockForTrait(
            $this->type,
            $this->constructorArgs,
            $this->mockClassName,
            $this->originalConstructor,
            $this->originalClone,
            $this->autoload,
            $this->methods,
            $this->cloneArguments
        );

        $this->testCase->registerMockObject($object);

        return $object;
    }

    /**
     * Specifies the subset of methods to mock. Default is to mock none of them.
     *
     * @deprecated https://github.com/sebastianbergmann/phpunit/pull/3687
     *
     * @return $this
     */
    public function setMethods(?array $methods = null): self
    {
        if ($methods === null) {
            $this->methods = $methods;
        } else {
            $this->methods = array_merge($this->methods ?? [], $methods);
        }

        return $this;
    }

    /**
     * Specifies the subset of methods to mock, requiring each to exist in the class.
     *
     * @param string[] $methods
     *
     * @throws CannotUseOnlyMethodsException
     * @throws ReflectionException
     *
     * @return $this
     */
    public function onlyMethods(array $methods): self
    {
        if (empty($methods)) {
            $this->emptyMethodsArray = true;

            return $this;
        }

        try {
            $reflector = new ReflectionClass($this->type);
            // @codeCoverageIgnoreStart
        } catch (\ReflectionException $e) {
            throw new ReflectionException(
                $e->getMessage(),
                (int) $e->getCode(),
                $e
            );
        }
        // @codeCoverageIgnoreEnd

        foreach ($methods as $method) {
            if (!$reflector->hasMethod($method)) {
                throw new CannotUseOnlyMethodsException($this->type, $method);
            }
        }

        $this->methods = array_merge($this->methods ?? [], $methods);

        return $this;
    }

    /**
     * Specifies methods that don't exist in the class which you want to mock.
     *
     * @param string[] $methods
     *
     * @throws CannotUseAddMethodsException
     * @throws ReflectionException
     * @throws RuntimeException
     *
     * @return $this
     */
    public function addMethods(array $methods): self
    {
        if (empty($methods)) {
            $this->emptyMethodsArray = true;

            return $this;
        }

        try {
            $reflector = new ReflectionClass($this->type);
            // @codeCoverageIgnoreStart
        } catch (\ReflectionException $e) {
            throw new ReflectionException(
                $e->getMessage(),
                (int) $e->getCode(),
                $e
            );
        }
        // @codeCoverageIgnoreEnd

        foreach ($methods as $method) {
            if ($reflector->hasMethod($method)) {
                throw new CannotUseAddMethodsException($this->type, $method);
            }
        }

        $this->methods = array_merge($this->methods ?? [], $methods);

        return $this;
    }

    /**
     * Specifies the subset of methods to not mock. Default is to mock all of them.
     *
     * @deprecated https://github.com/sebastianbergmann/phpunit/pull/3687
     *
     * @throws ReflectionException
     */
    public function setMethodsExcept(array $methods = []): self
    {
        return $this->setMethods(
            array_diff(
                $this->generator->getClassMethods($this->type),
                $methods
            )
        );
    }

    /**
     * Specifies the arguments for the constructor.
     *
     * @return $this
     */
    public function setConstructorArgs(array $args): self
    {
        $this->constructorArgs = $args;

        return $this;
    }

    /**
     * Specifies the name for the mock class.
     *
     * @return $this
     */
    public function setMockClassName(string $name): self
    {
        $this->mockClassName = $name;

        return $this;
    }

    /**
     * Disables the invocation of the original constructor.
     *
     * @return $this
     */
    public function disableOriginalConstructor(): self
    {
        $this->originalConstructor = false;

        return $this;
    }

    /**
     * Enables the invocation of the original constructor.
     *
     * @return $this
     */
    public function enableOriginalConstructor(): self
    {
        $this->originalConstructor = true;

        return $this;
    }

    /**
     * Disables the invocation of the original clone constructor.
     *
     * @return $this
     */
    public function disableOriginalClone(): self
    {
        $this->originalClone = false;

        return $this;
    }

    /**
     * Enables the invocation of the original clone constructor.
     *
     * @return $this
     */
    public function enableOriginalClone(): self
    {
        $this->originalClone = true;

        return $this;
    }

    /**
     * Disables the use of class autoloading while creating the mock object.
     *
     * @return $this
     */
    public function disableAutoload(): self
    {
        $this->autoload = false;

        return $this;
    }

    /**
     * Enables the use of class autoloading while creating the mock object.
     *
     * @return $this
     */
    public function enableAutoload(): self
    {
        $this->autoload = true;

        return $this;
    }

    /**
     * Disables the cloning of arguments passed to mocked methods.
     *
     * @return $this
     */
    public function disableArgumentCloning(): self
    {
        $this->cloneArguments = false;

        return $this;
    }

    /**
     * Enables the cloning of arguments passed to mocked methods.
     *
     * @return $this
     */
    public function enableArgumentCloning(): self
    {
        $this->cloneArguments = true;

        return $this;
    }

    /**
     * Enables the invocation of the original methods.
     *
     * @return $this
     */
    public function enableProxyingToOriginalMethods(): self
    {
        $this->callOriginalMethods = true;

        return $this;
    }

    /**
     * Disables the invocation of the original methods.
     *
     * @return $this
     */
    public function disableProxyingToOriginalMethods(): self
    {
        $this->callOriginalMethods = false;
        $this->proxyTarget         = null;

        return $this;
    }

    /**
     * Sets the proxy target.
     *
     * @return $this
     */
    public function setProxyTarget(object $object): self
    {
        $this->proxyTarget = $object;

        return $this;
    }

    /**
     * @return $this
     */
    public function allowMockingUnknownTypes(): self
    {
        $this->allowMockingUnknownTypes = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function disallowMockingUnknownTypes(): self
    {
        $this->allowMockingUnknownTypes = false;

        return $this;
    }

    /**
     * @return $this
     */
    public function enableAutoReturnValueGeneration(): self
    {
        $this->returnValueGeneration = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function disableAutoReturnValueGeneration(): self
    {
        $this->returnValueGeneration = false;

        return $this;
    }
}
