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
        
        $container->setParameter('superparameter', 'superparametervalue');
        
        $docParser = new \Doctrine\Common\Annotations\DocParser();
        $docParser->setAutoloadAnnotations(true);
        $docParser->setIgnoreNotImportedAnnotations(true);
        
        $reader = new \Doctrine\Common\Annotations\AnnotationReader($docParser);
        
        $serviceBuilder = new \Ifschleife\Bundle\AutowiringBundle\Autowiring\ServiceBuilder(
            new \Ifschleife\Bundle\AutowiringBundle\DependencyInjection\Loader\AnnotatedFileLoader(
                $container, 
                new \Ifschleife\Bundle\AutowiringBundle\Autowiring\Injector\ContainerInjector($container, 
                        $reader
                ),
                new \Symfony\Component\Config\FileLocator(),
                new \Ifschleife\Bundle\AutowiringBundle\Autowiring\Parser\PhpParser()
        ));
        
        $serviceBuilder->setFiles($files);
        
        $serviceBuilder->build();
        
        $classnameMapper = new \Ifschleife\Bundle\AutowiringBundle\Autowiring\ClassnameMapper($container);
        
        $propertyInjector    = new \Ifschleife\Bundle\AutowiringBundle\Autowiring\Injector\PropertyInjector($container, $reader);
        $propertyInjector->setWireByName(true);
        $propertyInjector->setServiceNameSuffix('Service');
     
        $constructorInjector = new \Ifschleife\Bundle\AutowiringBundle\Autowiring\Injector\ConstructorInjector($container, $reader, $classnameMapper);
        $constructorInjector->setWireByType(true);
        
        $setterInjector      = new \Ifschleife\Bundle\AutowiringBundle\Autowiring\Injector\SetterInjector($container, $reader, $classnameMapper);
        $setterInjector->setWireByType(true);
        
        // ContainerBuilder $container, ClassnameMapper $classname_mapper, PropertyInjector $property_injector, ConstructorInjector $constructor_injector, SetterInjector $setter_injector
        $dependencyResolver = new \Ifschleife\Bundle\AutowiringBundle\Autowiring\DependencyResolver(
            $container,
            $classnameMapper,
            $propertyInjector, 
            $constructorInjector,
            $setterInjector
        );
        
        $dependencyResolver->resolve();

        $this->assertTrue($container->hasDefinition('ifschleife.bundle.autowiring_bundle.tests.fixtures.create_service_testclass1'));
        $this->assertTrue($container->hasDefinition('hans.wurst'));
        $this->assertTrue($container->hasDefinition('ifschleife.autowiring.testclass'));
        $this->assertTrue($container->hasDefinition('ifschleife.bundle.autowiring_bundle.tests.fixtures.parent_testclass'));
        $this->assertTrue($container->hasDefinition('ifschleife.autowiring.testservice'));
        
        $definitions = $container->getDefinitions();
        
        $properties  = $definitions['ifschleife.autowiring.testclass']->getProperties();
        
        $this->assertArrayHasKey('ifschleifeAutowiringTestserviceService', $properties);
        
        $this->assertArrayHasKey('testsvc2', $properties);
        
        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Reference', $properties['testsvc2']);
        
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
            'autowiring.test_implementation',
            $methodCalls[0][1][0]->__toString()
        );
        
        $this->assertInstanceOf(
            '\Symfony\Component\DependencyInjection\Reference',
            $methodCalls[1][1][0]
        );
        
        $this->assertEquals(
            'ifschleife.autowiring.testservice',
            $methodCalls[1][1][0]->__toString()
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
            '\Symfony\Component\DependencyInjection\Parameter',
            $methodCalls[3][1][0]
        );
        
        $this->assertInstanceOf(
            '\Symfony\Component\DependencyInjection\Parameter',
            $methodCalls[3][1][1]
        );
        
        $this->assertInstanceOf(
            '\Symfony\Component\DependencyInjection\Reference',
            $methodCalls[4][1][0]
        );
        
        $this->assertInstanceOf(
            '\Symfony\Component\DependencyInjection\Parameter',
            $methodCalls[4][1][1]
        );
        
        $this->assertInstanceOf(
            '\Symfony\Component\DependencyInjection\Parameter',
            $methodCalls[5][1][0]
        );
        
        $this->assertInstanceOf(
            '\Symfony\Component\DependencyInjection\Reference',
            $methodCalls[5][1][1]
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
            'foo',
            $container->getParameter($methodCalls[3][1][0]->__toString())
        );
        
        $this->assertEquals(
            'bar',
            $container->getParameter($methodCalls[3][1][1]->__toString())
        );
        
        $this->assertEquals(
            'ifschleife.autowiring.testservice',
            $methodCalls[4][1][0]->__toString()
        );
        
        $this->assertEquals(
            'bar',
            $container->getParameter($methodCalls[4][1][1]->__toString())
        );
        
        $this->assertEquals(
            'bar',
            $container->getParameter($methodCalls[5][1][0]->__toString())
        );
        
        $this->assertEquals(
            'ifschleife.autowiring.testservice',
            $methodCalls[5][1][1]->__toString()
        );
    }
    
    /**
     * 
     */
    public function failOptionalArguments()
    {
        $container = new \Symfony\Component\DependencyInjection\ContainerBuilder();
        
        $docParser = new \Doctrine\Common\Annotations\DocParser;
        $docParser->setAutoloadAnnotations(true);
        $docParser->setIgnoreNotImportedAnnotations(true);
        
        $reader = new \Doctrine\Common\Annotations\AnnotationReader($docParser);
        
        $serviceBuilder = new \Ifschleife\Bundle\AutowiringBundle\Autowiring\ServiceBuilder(
            new \Ifschleife\Bundle\AutowiringBundle\DependencyInjection\Loader\AnnotatedFileLoader(
                $container, 
                new \Ifschleife\Bundle\AutowiringBundle\Autowiring\Injector\ContainerInjector($container, 
                        $reader
                ),
                new \Symfony\Component\Config\FileLocator(),
                new \Ifschleife\Bundle\AutowiringBundle\Autowiring\Parser\PhpParser()
        ));
        
        $serviceBuilder->setFiles(array(__DIR__ . '/../Fixtures/OptionalInjectionFailclass.php'));
        
        $serviceBuilder->build();
        
        $classnameMapper = new \Ifschleife\Bundle\AutowiringBundle\Autowiring\ClassnameMapper($container);
        
        $propertyInjector = new \Ifschleife\Bundle\AutowiringBundle\Autowiring\Injector\PropertyInjector($container, $reader);
        $propertyInjector->setWireByName(true);
        $propertyInjector->setServiceNameSuffix('Service');
     
        $constructorInjector = new \Ifschleife\Bundle\AutowiringBundle\Autowiring\Injector\ConstructorInjector($container, $reader, $classnameMapper);
        $constructorInjector->setWireByType(true);
        
        $setterInjector = new \Ifschleife\Bundle\AutowiringBundle\Autowiring\Injector\SetterInjector($container, $reader, $classnameMapper);
        $setterInjector->setWireByType(true);
        
        // ContainerBuilder $container, ClassnameMapper $classname_mapper, PropertyInjector $property_injector, ConstructorInjector $constructor_injector, SetterInjector $setter_injector
        $dependencyResolver = new \Ifschleife\Bundle\AutowiringBundle\Autowiring\DependencyResolver(
            $container,
            $classnameMapper,
            $propertyInjector, 
            $constructorInjector,
            $setterInjector
        );
        
        try 
        {
            $dependencyResolver->resolve();   
        }
        catch(Exception $e)
        {
            $this->assertInstanceOf('Ifschleife\Bundle\AutowiringBundle\Autowiring\Injector\UnresolvedReferenceException', $e);
        }
        
    }
    
    /**
     * @dataProvider optionalFiles
     */
    public function testOptionalArguments($file)
    {
        $container = new \Symfony\Component\DependencyInjection\ContainerBuilder();
        
        $docParser = new \Doctrine\Common\Annotations\DocParser();
        $docParser->setAutoloadAnnotations(true);
        $docParser->setIgnoreNotImportedAnnotations(true);
        
        $reader = new \Doctrine\Common\Annotations\AnnotationReader($docParser);
        
        $serviceBuilder = new \Ifschleife\Bundle\AutowiringBundle\Autowiring\ServiceBuilder(
            new \Ifschleife\Bundle\AutowiringBundle\DependencyInjection\Loader\AnnotatedFileLoader(
                $container, 
                new \Ifschleife\Bundle\AutowiringBundle\Autowiring\Injector\ContainerInjector($container, 
                        $reader
                ),
                new \Symfony\Component\Config\FileLocator(),
                new \Ifschleife\Bundle\AutowiringBundle\Autowiring\Parser\PhpParser()
        ));
        
        $serviceBuilder->setFiles(array($file));
        
        $serviceBuilder->build();
        
        $classnameMapper = new \Ifschleife\Bundle\AutowiringBundle\Autowiring\ClassnameMapper($container);
        
        $propertyInjector = new \Ifschleife\Bundle\AutowiringBundle\Autowiring\Injector\PropertyInjector($container, $reader);
        $propertyInjector->setWireByName(true);
        $propertyInjector->setServiceNameSuffix('Service');
     
        $constructorInjector = new \Ifschleife\Bundle\AutowiringBundle\Autowiring\Injector\ConstructorInjector($container, $reader, $classnameMapper);
        $constructorInjector->setWireByType(true);
        
        $setterInjector = new \Ifschleife\Bundle\AutowiringBundle\Autowiring\Injector\SetterInjector($container, $reader, $classnameMapper);
        $setterInjector->setWireByType(true);
        
        // ContainerBuilder $container, ClassnameMapper $classname_mapper, PropertyInjector $property_injector, ConstructorInjector $constructor_injector, SetterInjector $setter_injector
        $dependencyResolver = new \Ifschleife\Bundle\AutowiringBundle\Autowiring\DependencyResolver(
            $container,
            $classnameMapper,
            $propertyInjector, 
            $constructorInjector,
            $setterInjector
        );
        
        $dependencyResolver->resolve();
        
        $this->assertInstanceOf('Ifschleife\Bundle\AutowiringBundle\Tests\Fixtures\OptionalInjectionTestclass', $container->get('autowiring.optional_injection_testclass'));
    }
    
    function optionalFiles()
    {
        return array(
            array(
                __DIR__ . '/../Fixtures/OptionalInjectionTestclass.php'
            )
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
                    __DIR__ . '/../Fixtures/Testservice.php',
                    __DIR__ . '/../Fixtures/TestImplementation.php',
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
                    __DIR__ . '/../Fixtures/TestImplementation.php',
                    
                ),
            )
        );
    }
}