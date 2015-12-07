# TestIt Library

TestIt is an add-on library for PHPUnit that extends mocking engine and allows comfort testing of class interactions with surrounding environment.
Are you tired using $this->getMock()->expects()->method()->with()->will() constructions? TestIt is for you :-)

Usual unit test consists from several steps:
- **data preparation** - prepare all data you'll need during the test. Input data, partial results (to return from mocked dependencies), expected results etc.
- **mocking of dependencies** - creating of "fake" dependencies to not interact with the real environment during the test
- **creating new instance of the object to test** - for every test new (clean) instance is necessary
- **expecting what should happen** - defining expectations of dependencies calls and its behaviour (returning specific value, throwing exception etc.)
- **putting instance of tested object to the defined state** - starting conditions are important for the test in order to be deterministic
- **calling method (methods) on testing instance**
- **asserting end conditions** - asserting that all expectations are met, all results are as expected and test instance is in expected end state

TestIt can help you with most of the steps.

## Compatibility

- Version 1.* is compatible with PHPUnit 3.7.*
- Version 2.* is compatible with PHPUnit 4.*
- Version 3.* is compatible with PHPUnit 5.*

We are running our unit tests against PHP versions 5.3, 5.4, 5.5, 7 and against HHVM.

Please report any bugs into GitHub. Thank you.

## License

You can use TestIt library under MIT license.

## Installation

Use [composer](http://getcomposer.org) to install TestIt. Just add **arron/testit** project as your dependency.

## Testing with TestIt

TestIt offers own TestCase class that is inherited from \PHPUnit_Framework_TestCase and enhancing it with some features. It is meant to be base class for your tests.
It is helping you with creating mocks of your class dependencies and verifying those dependencies are called as expected.
To use this features, just inherit your test base class from \Arron\TestIt\TestCase class and you are free to go.

The best way how to profit from this library is to integrate it to your tests so you will be able to use all those features even more easily.

**For examples, check examples directory.**

### Creating of the object to test

There is abstract method createTestObject() defined in \Arron\TestIt\TestCase namespace. It should return created instance ready to use for testing. So createTestObject() is the right place for any initialization mock injections etc. It will be called during the setUp call. So just concentrate on new object creation, the rest is in hands of TestIt :-)
If you need to call any expectations about any dependencies called in the time of test object creation, overwrite **initializationExpectations** method and place your expectations there. This method will be called just before object creation in the setUp method.

### Mocking of dependencies

TestIt is helping you with creating of mocked classes. It contains something like object locator so, it is not creating new mock every time.
But you can get the best value if you integrate TestIt capabilities with your DI.

#### Creating mocked class

You create mock by simply call simple method in your test class:

```php
$mock = $this->getMockedClass($className, $mockName);
```

Notice that all created mocks have to have unique name. With this name, you are referencing them in your expectations. It also allows you to have more mocks of the same class (with different names).
Mock-creation engine uses native PHPUnit mocking, automatically mocking all public methods and adds call tracking feature.
All you have to do is to pass the mock to your class you are testing to be called from there.

#### Creating mocks of global functions

TestIt allows you to mock global functions. Do it by calling mockGlobalFunction function.

```php
protected function mockGlobalFunction($name, $namespace = NULL)

//just call it before using global function
$this->mockGlobalFunction('time', 'YourNamespace');
//and then expect the call
$this->expectDependencyCall('global', 'time', array(), 123456789);
```

Namespace argument is the namespace your time() function is called from. If not provided, it will be set to the namespace mockGlobalFunction is called from.

This is done by a namespace trick. If you are in namespace and call function, it will be searched first in the current namespace and then in the global space.
So if not defined in the namespace, it will fall-back to global function. mockGlobalFunction will define mock of the function in specific namespace so the call will no longer
fall-back to global space and mocked function will be called. Notice, that once the function is defined in some namespace, it will be called from anywhere in this namespace.
Nevertheless TestIt will check and won't redefine it again. But in terms of readability of your code, call mockGlobalFunction everywhere you need it to be clear you are mocking something.

Therefore there is limitation here. You can mock only functions that are called with unqualified name.

```php
$timestamp = time();//can be mocked

$timestamp = \time();//can NOT be mocked
```

There is one thing that is not working yet. Functions with output argument can be mocked but output argument won't work. The main problem is, how to detect output arguments? Any ideas? :-)

### Expecting what should happen

In your tests, you are testing inputs and outputs, but you also have to test class interactions with its dependencies. Meaning you have to test, that there were correct methods called with correct parameters in correct order. These methods usually return some data so it is necessary to ensure, that this data will be returned.

In TestIt it is very easy to do so.

```php
//declaration of expectation method
public function expectDependencyCall($dependencyName, $methodName, $methodArguments = array(), $returnValue = NULL)

//practical use
$this->expectDependencyCall("articleModel", "get", array(10, 5), array());
//expects that on the "articleModel" dependency "get" method will be called with arguments (offset, limit) 10, 5 and it will return empty array

//valid call in tested class would be for example
$this->articleModel->get(10,5); //where $this->articleModel is a property where mock named "articleModel" is stored
```

Methods arguments will be expected to be exactly the same as you passed them (in the array). Empty array means no arguments, NULL means that you don't care so any arguments will be accepted.
Optional arguments (with default value) can be omitted.

As for return value, all values passed here will be returned by mock call unchanged except instances of \Exception class (and its ancestors of course). These instances will be thrown as exception.
It allows you to simulate cases where one of your dependencies fails.

### Defining state of tested object

Before launching the test, you must put tested object to defined state. Against this state you will assert changes after the test. In order to have real unit test,
you shouldn’t use object's set methods etc. TestIt provides you two methods for this purpose.

#### setPropertyInTestSubject

```php
protected function setPropertyInTestSubject($name, $value)

//example
$this->setPropertyInTestSubject('state', 'notReady'); //will set property 'state' to value 'notReady' in test object
```

#### getPropertyFromTestSubject

```php
protected function getPropertyFromTestSubject($name)

//example
$this->getPropertyFromTestSubject('state'); //return value of the 'state' property in test object
```

You can set/get any defined property from tested object. It is using reflection. I noticed random occurrences of errors here. I wasn't able to catch specific circumstances
so far, but most of the time, it is working fine :-)

### Call method to test

Use getTestObject() method to access instance of test object. If not created yet, fresh instance will be created using the createTestObject() method.

From time to time, it can be useful to call/test also a protected/private method. TestIt provides you a simple method to call these methods.

```php
protected function callTestSubjectMethod($name, array $arguments = array())

//example
$this->callTestSubjectMethod('setState', array('ready')); //will call 'setState' method with one argument 'ready',  even it is protected/private
```

It is not a good habit to call inaccessible methods but it can be handy :-)

### Asserting end conditions

TestIt will automatically assert your expectations. For every dependency call these assertions will be done runtime (in this order):
- correct method on correct dependency is called in correct order (defined by order of expectations)
- actual arguments of the method called are asserted against expected ones. If there is any of the methods arguments optional (with default value) and it is omitted, default value will be filled to the expectation.
If any of this assertion fails the test will fail immediately. Meaningful error message is generated, so it is clear what should have happened and what actually happened.

### Remote debugging of PHP cli scripts

From version 1.2.0 TestIt comes with bash script, that allowes you to debug PHP cli scripts, running on server, on your local mashine. You can find **debugphpscript** in your vendor/bin directory.
All you have to do is configure your IDE to be able of remote debugging with xDebug (consult help for your IDE), set your IDE for listening for incoming xDebug session and than launch your
cli script you want to debug through **debugphpscript**. You have to specify session_id of xDebug session, in some cases (ex. PhpStorm) you have to specify name of you server
configuration in IDE, IP address of your local mashine is needed and of course, you have to specify what command you want to run.

```
./debugphpscript -id PhpStorm -s myserver.local -ip 192.168.1.1 -c "my_script.php -f someConfiguration"
```

