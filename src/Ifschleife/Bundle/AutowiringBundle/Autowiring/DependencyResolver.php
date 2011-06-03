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

namespace Ifschleife\Bundle\AutowiringBundle\Autowiring;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Doctrine\Common\Annotations\Reader;

use Ifschleife\Bundle\AutowiringBundle\Annotation\AnnotationReaderDecorator;

use Ifschleife\Bundle\AutowiringBundle\Autowiring\Injector\Injector;
use Ifschleife\Bundle\AutowiringBundle\Autowiring\Injector\PropertyInjector;
use Ifschleife\Bundle\AutowiringBundle\Autowiring\Injector\SetterInjector;
use Ifschleife\Bundle\AutowiringBundle\Autowiring\Injector\ConstructorInjector;

/**
 * DependencyResolver
 * 
 * This class maps @Inject class annotations in each service class to primitive
 * values or services.
 * 
 * @author joshi
 */
class DependencyResolver
{
    /**
     * @var ContainerBuilder
     */
    private $container;
    
    /**
     * @var boolean
     */
    private $isInitialized;
    
    /**
     * @var ConstructorInjector
     */
    private $constructorInjector;
    
    /**
     * @var PropertyInjector
     */
    private $propertyInjector;
    
    /**
     * @var SetterInjector;
     */
    private $setterInjector;
    
    /**
     * @var ClassnameMapper;
     */
    private $classNameMapper;
    
    /**
     * Constructor
     * 
     * @param ContainerBuilder $container: The container builder
     */
    public function __construct(ContainerBuilder $container, Reader $reader = null)
    {
        $this->container = $container;
        
        $reader = null === $reader ? new AnnotationReaderDecorator() : $reader;
        
        $this->propertyInjector     = new PropertyInjector($container, $reader);
        $this->constructorInjector  = new ConstructorInjector($container, $reader);
        $this->setterInjector       = new SetterInjector($container, $reader);
        
        $this->isInitialized = false;
    }

    /**
     * Initializes all instance variables.
     * 
     * @return void
     */
    public function initialize()
    {
        $this->classNameMapper = new ClassnameMapper($this->container);
        
        $this->propertyInjector->setClassNameMapper($this->classNameMapper);
        $this->constructorInjector->setClassNameMapper($this->classNameMapper);
        $this->setterInjector->setClassNameMapper($this->classNameMapper);
        
        $this->isInitialized = true;
    }
    
    /**
     * Starts the service resolving by analyzing the
     * service classes´s property and method annotations.
     *  
     * @return void
     */
    public function resolve()
    {
        if( ! $this->isInitialized)
        {
            $this->initialize();
        }
        
        $this->extendDefinitions();
        
        $this->isInitialized = false;
    }

    /**
     * Parses all service classes and extends the container´s definitions
     * by annotated Dependency Injection hints.
     * 
     * @return void
     */
    private function extendDefinitions()
    {
        // ANALYZE SERVICES
        foreach ($this->classNameMapper->getClasses() AS $class)
        {
            $service_id = $this->classNameMapper->getServiceId($class->getName());
            
            // AMBIGUOUS CLASSNAMES ARE NOT ALLOWED TO BE PROCESSED ...
            if(false !== $service_id)
            {
                $definition = $this->container->getDefinition($service_id);

                $this->extendConstructorInjections($definition, $class);

                $this->extendSetters($definition, $class);

                $this->extendPropertyInjections($definition, $class);
            }
        }
    }

    /**
     * Adds parameters to the current dispatched service definition configured
     * by annotations.
     *
     * @param Definition $definition
     * @param \ReflectionClass $class 
     */
    private function extendConstructorInjections(Definition $definition, \ReflectionClass $class)
    {   
        $constructor = $class->getConstructor();

        if (null !== $constructor)
        {
            $this->constructorInjector->inject($definition, $constructor);
        }
    }

    /**
     * Adds property injections to the DIC based on annotations.
     * 
     * @param  $definition
     * @param \ReflectionClass $class 
     */
    private function extendPropertyInjections(Definition $definition, \ReflectionClass $class)
    {
        foreach ($class->getProperties() AS $property)
        {
            $this->propertyInjector->inject($definition, $property);
        }
    }
    
    /**
     * Adds method calls to the DIC configured by class annotations.
     * 
     * @todo Optimize getMethods() call by using \ReflectionMethod constants
     * @param Definition $definition
     * @param \ReflectionClass $class 
     */
    private function extendSetters(Definition $definition, \ReflectionClass $class)
    {
        foreach ($class->getMethods() AS $method)
        {
            if($method->isConstructor() || $method->isDestructor() || $method->isStatic() || $method->isAbstract()) continue;
            
            $this->setterInjector->inject($definition, $method);
        }
    }
}
