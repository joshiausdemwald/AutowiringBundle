<?php
/**
 * Copyright (c) 2011 Johannes Heinen <johannes.heinen@gmail.com>
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy 
 * of this software and associated documentation files (the "Software"), to deal 
 * in the Software without restriction, including without limitation the rights 
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell 
 * copies of the Software, and to permit persons to whom the Software is 
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in 
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR 
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, 
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE 
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER 
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, 
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE 
 * SOFTWARE.
 */

namespace Ifschleife\Bundle\AutowiringBundle\Tests\Autowiring;

/**
 * DependencyResolverTest
 *
 * @author joshi
 */
class DependencyResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider files 
     */
    public function testDependencyResolving(array $files)
    {
        $container = new \Symfony\Component\DependencyInjection\ContainerBuilder();
        
        $serviceBuilder = new \Ifschleife\Bundle\AutowiringBundle\Autowiring\ServiceBuilder($container);
        
        $serviceBuilder->setFiles($files);
        
        $serviceBuilder->build();
        
        $dependencyResolver = new \Ifschleife\Bundle\AutowiringBundle\Autowiring\DependencyResolver($container);
        
        $dependencyResolver->resolve();

        $this->assertTrue($container->hasDefinition('ifschleife.bundle.autowiring_bundle.tests.fixtures.create_service_testclass1'));
        $this->assertTrue($container->hasDefinition('hans.wurst'));
        $this->assertTrue($container->hasDefinition('ifschleife.autowiring.testclass'));
        $this->assertTrue($container->hasDefinition('ifschleife.bundle.autowiring_bundle.tests.fixtures.parent_testclass'));
        $this->assertTrue($container->hasDefinition('ifschleife.autowiring.testservice'));

        $definitions = $container->getDefinitions();
        
        $this->assertInstanceOf(
                '\Symfony\Component\DependencyInjection\Reference', 
                $definitions['ifschleife.autowiring.testclass']->getArgument(0)
        );
        
        $properties = $definitions['ifschleife.autowiring.testclass']->getProperties();
        
        $this->assertInstanceOf(
                '\Symfony\Component\DependencyInjection\Reference', 
                $properties['ifschleifeAutowiringTestserviceService']
        );
        
        $this->assertInstanceOf('\Symfony\Component\DependencyInjection\DefinitionDecorator', $definitions['ifschleife.autowiring.testclass']);
        
        $this->assertEquals('ifschleife.bundle.autowiring_bundle.tests.fixtures.parent_testclass', $definitions['ifschleife.autowiring.testclass']->getParent());
        
        $this->assertTrue(
            $definitions['ifschleife.autowiring.testclass']->hasMethodCall('setTestservice')
        );
        
        $this->assertTrue(
            $definitions['ifschleife.autowiring.testclass']->hasMethodCall('setFoo')
        );
        
        $this->assertTrue(
            $definitions['ifschleife.autowiring.testclass']->hasMethodCall('setBar')
        );
        
        $this->assertTrue(
            $definitions['ifschleife.autowiring.testclass']->hasMethodCall('setFoobar1')
        );
        
        $this->assertTrue(
            $definitions['ifschleife.autowiring.testclass']->hasMethodCall('setFoobar2')
        );
        
        $methodCalls = $definitions['ifschleife.autowiring.testclass']->getMethodCalls();
        
        $this->assertInstanceOf(
            '\Symfony\Component\DependencyInjection\Reference',
            $methodCalls[0][1][0]
        );
        
        $this->assertEquals(
            'ifschleife.autowiring.testservice',
            $methodCalls[0][1][0]->__toString()
        );
        
        $this->assertInstanceOf(
            '\Symfony\Component\DependencyInjection\Parameter',
            $methodCalls[1][1][0]
        );
        
        $this->assertInstanceOf(
            '\Symfony\Component\DependencyInjection\Parameter',
            $methodCalls[1][1][1]
        );
        
        $this->assertInstanceOf(
            '\Symfony\Component\DependencyInjection\Parameter',
            $methodCalls[2][1][0]
        );
        
        $this->assertInstanceOf(
            '\Symfony\Component\DependencyInjection\Parameter',
            $methodCalls[2][1][1]
        );
        
        $this->assertInstanceOf(
            '\Symfony\Component\DependencyInjection\Reference',
            $methodCalls[3][1][0]
        );
        
        $this->assertInstanceOf(
            '\Symfony\Component\DependencyInjection\Parameter',
            $methodCalls[3][1][1]
        );
        
        $this->assertInstanceOf(
            '\Symfony\Component\DependencyInjection\Parameter',
            $methodCalls[4][1][0]
        );
        
        $this->assertInstanceOf(
            '\Symfony\Component\DependencyInjection\Reference',
            $methodCalls[4][1][1]
        );
        
        $this->assertEquals(
            'foo',
            $container->getParameter($methodCalls[1][1][0]->__toString())
        );
        
        $this->assertEquals(
            'bar',
            $container->getParameter($methodCalls[1][1][1]->__toString())
        );
        
        $this->assertEquals(
            'foo',
            $container->getParameter($methodCalls[2][1][0]->__toString())
        );
        
        $this->assertEquals(
            'bar',
            $container->getParameter($methodCalls[2][1][1]->__toString())
        );
        
        $this->assertEquals(
            'ifschleife.autowiring.testservice',
            $methodCalls[3][1][0]->__toString()
        );
        
        $this->assertEquals(
            'bar',
            $container->getParameter($methodCalls[3][1][1]->__toString())
        );
        
        $this->assertEquals(
            'bar',
            $container->getParameter($methodCalls[4][1][0]->__toString())
        );
        
        $this->assertEquals(
            'ifschleife.autowiring.testservice',
            $methodCalls[4][1][1]->__toString()
        );
    }
    
    function files()
    {
        return array(
            array(
                array(
                    __DIR__ . '/../Fixtures/CreateServiceMalformed', 
                    __DIR__ . '/../Fixtures/Nofile', 
                    __DIR__ . '/../Fixtures/CreateServiceTestclass1.php',
                    __DIR__ . '/../Fixtures/CreateServiceTestclass2.php',
                    
                    // relevant files
                    __DIR__ . '/../Fixtures/ParentTestclass.php',
                    __DIR__ . '/../Fixtures/Testclass.php',
                    __DIR__ . '/../Fixtures/Testservice.php'
                ),
            ),
            array(
                array(
                    __DIR__ . '/../Fixtures/CreateServiceMalformed', 
                    __DIR__ . '/../Fixtures/Nofile', 
                    __DIR__ . '/../Fixtures/CreateServiceTestclass1.php',
                    __DIR__ . '/../Fixtures/CreateServiceTestclass2.php',
                    
                    // relevant files
                    __DIR__ . '/../Fixtures/Testclass.php',
                    __DIR__ . '/../Fixtures/Testservice.php',
                    __DIR__ . '/../Fixtures/ParentTestclass.php',
                    
                ),
            )
        );
    }
}