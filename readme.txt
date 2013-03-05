TestIt Library
**************

TestIt is an addon library for PHPUnit that extends mocking engine and allowes comfort testing of class interactions with surrounding environment.
Are you tired using $this->getMock()->expects()->method()->with()->will() constructions? TestIt is for you :-)

How to use it
==============

TestIt offers own TestCase class that is inherited from \PHPUnit_Framework_TestCase and enhancing it with some features. It is ment to be base class for your tests.
It is helping you with creating mocks of your class dependecies and verifying those dependecies are called as expected.
To use this features, just inherit your test base class from \Arron\TestIt\TestCase class and you are free to go.

The best way how to profit from this library is to integrate it to your tests so you will be able to use all those features even more easier.

Creating mock of classes
------------------------
You create mock by simply call simple method in your test claas:

/---code php
$mock = $this->getMockedClass($className, $mockName);
\---

Notice that all created mocks has to have unique name. With this name, you are referencing them in your expectations. It also allowes you to have more mocks of the same class (with different names).
Mock-creation engine uses native PHPUnit mocking, automatically mocking all public methods and adds call tracking feature.
All you have to do is to pass the mock to your class you are testing to be called from there.

Creating mocks of global funtions
---------------------------------

TestIt allowes you to mock global functions. Do it by calling mockGlobalFunction function.

/---code php
protected function mockGlobalFunction($name, $namespace = NULL)


//just call it before using global function
$this->mockGlobalFunction('time', '\YourNamespace');
//and than expect the call
$this->expectDependencyCall('global', 'time', array(), 123456789);
\---

Namespace argument is the namespace your time() function is called from. If not provided, it will be set to the namespace mockGlobalFunction is called from.

This is done by a namespace trick. If you are in namespace and call function, it will be searched first in the current namespace and than in the global space.
So if not defined in the namespace, it will fallback to global function. mockGlobalFunction will define mock of the function in specific namespace so the call will no longer
fallback to global space and mocked function will be called. Notice, that once the function is defined in some namespace, it will be called from anywhere in this namespace.
Nevertheless TestIt will chceck and won't redefine it again. But in terms of readability of your code, call mockGlobalFunction everywhere you need it to be clear you are mocking something.

Therefore there is limitation here. You can mock only functions that are called with unqualified name.

/---code php
$timestamp = time();//can be mocked

$timestamp = \time();//can NOT be mocked
\---

There is one thing that is not working yet. Functions with output argument can be mocked but output argument won't work. The main problem is, how to detect output arguments? Any ideas? :-)


Expectaions
-----------

In your tests, you are testing inputs and outputs, but you also have to test class interactions with its dependencies. Meaning you have to test, that there were correct methods called
with correct parameters in correct order. These metods usunally return some data so it is necessary to ensure, that this data will be returned.

In TestIt it is very easy to do so.

/---code php
//declaration of expectation method
public function expectDependencyCall($dependencyName, $methodName, $methodArguments = array(), $returnValue = NULL)

//practical use
$this->expectDependencyCall("articleModel", "get", array(10, 5), array());
//expects that on the "articleModel" dependency "get" method will be called with arguments (offset, limit) 10, 5 and it will return empty array
//valid call in tested class would be for example
$this->articleModel->get(10,5); //where $this->articleModel is a property where mock named "articleModel" is stored
\---

Methods arguments will be expected to be exacly the same as you passed them (in the array). Empty array means no arguments, NULL means that you don't care so any arguments will be accepted.
Optional arguments (with default value) can be omitted.

As for return value, all values passed here will be returned by mock call unchanged except instances of \Exception class (and its ansestors of course). This instances will be thrown as exception.
It allowes you to simulate cases where one of your dependecies failes.

Assertions
----------

TestIt will automatically assert your expectations. For every dependency call these assertions will be done runtime (in this order):
  - correct method on correct dependency is called in correct order (defined by order of expectations)
  - actual arguments of the method called is asserted against expected ones. If there is any of the methods arguments optional (with default value) and it is omitted,
    default value will be filled to the expectation.

If any of this assertion fails the test will fail immediately. Meaningful error message is generated, so it is clear what should have heppened and what actually happened.
