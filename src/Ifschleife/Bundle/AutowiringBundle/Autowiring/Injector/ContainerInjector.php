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

namespace Ifschleife\Bundle\AutowiringBundle\Autowiring\Injector;

use Ifschleife\Bundle\AutowiringBundle\Autowiring\Inflector;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Container;

/**
 * ContainerInjector loads services into the DIC
 *
 * @author joshi
 */
class ContainerInjector extends Injector
{
    protected function process(Definition $definition, \Reflector $reflector)
    {
        $this->parseDefinition($reflector);
    }
    
    /**
     * @param Definition $definition
     * @param \Reflector $class
     * @return string the service id
     */
    public function injectService(\ReflectionClass $class)
    {
        $this->annotations = $this->createAnnotationsMap((array)$this->readAnnotations($class));
        
        return $this->doInject($class);
    }
    
    /**
     * Does the injection.
     * 
     * @param \ReflectionClass $class
     * @return String $service_Id: The services id´s.
     */
    protected function doInject(\ReflectionClass $class)
    {
        if($this->hasAnnotation(Injector::ANNOTATION_SERVICE))
        {
            $annotation = $this->getAnnotation(Injector::ANNOTATION_SERVICE);
            
            $service_id = $annotation->getId();

            if(null === $service_id)
            {
                $service_id = $this->generateServiceId($class);
            }

            if($this->container->hasDefinition($service_id))
            {
                return $service_id;
            }
            
            $definition = $this->createDefinition($class);
            
            if($class->isAbstract())
            {
                $definition->setAbstract(true);
            }
            
            // @todo: refactor to unify @/% detection
            if(null !== ($callable = $annotation->getFactoryMethod()))
            {
                if(is_string($callable))
                {
                    $definition->setFactoryClass($definition->getClass());
                    $definition->setFactoryMethod($callable);
                }
                elseif(is_array($callable))
                {
                    $callable = array_values($callable);
                    
                    if(count($callable) != 2)
                    {
                        throw new BadCallableException(sprintf('A factory callable must either be a string (a method name which refers to "self") or any valid php callable defined as an array in class "%s".', $class->getName()));
                    }
                    // SERVICE OR CLASS?
                    if(0 === strpos($callable[0], '@'))
                    {
                        $service_name = substr($callable[0], 1);
                        
                        $definition->setFactoryService($service_name);
                    }
                    else
                    {
                        $definition->setFactoryClass($callable[0]);
                    }
                    $definition->setFactoryMethod($callable[1]);
                }
                else
                {
                    throw new BadCallableException(sprintf('A factory callable must either be a string (a method name which refers to "self") or any valid php callable defined as an array in service class "%s".', $class->getName()));
                }
            }
            
            if(null !== $callable = $annotation->getConfigurator())
            {
                if(is_string($callable) && 0 === strpos($callable, "function"))
                {
                    $callable = eval('return ' . $callable . ';');
                }
                
                if( ! is_callable($callable))
                {
                    throw new BadCallableException(sprintf('The given Configurator is not a valid php callable in class "%s".', $class->getName()));
                }
                
                $definition->setConfigurator($callable);
            }
            
            $definition->setFile($annotation->getFile());
            
            $definition->setPublic($annotation->getPublic());
            
            $scope = Container::SCOPE_CONTAINER;
            
            if(null !== $annotation->getScope())
            {
                $scope = $annotation->getScope();
            }
            
            $definition->setScope($scope);
            
            $definition->setTags($annotation->getTags());
            
            $this->container->setDefinition($service_id, $definition);
            
            return $service_id;
        }
        
        return null;
    }
    
    /**
     * Creates a definition from a class and returns it.
     *
     * @param \ReflectionClass $class 
     * @return Definition $definition
     */
    protected function createDefinition(\ReflectionClass $class)
    {
        $definition = null; 
        
        if(false !== ($parentClass = $class->getParentClass()))
        {
            $parent_service_id = $this->injectService($parentClass);
            
            $definition = new DefinitionDecorator($parent_service_id);
        }
        else
        {
            $definition = new Definition();
        }
        
        $definition->setClass($class->getName());
        
        $definition->setPublic(true);
        
        return $definition;
    }
    
    /**
     * Reads the method annotations and returns them as a flat array.
     * 
     * @param \Reflector $method 
     */
    protected function readAnnotations(\Reflector $class)
    {
       return $this->reader->getClassAnnotations($class);
    }
    
    /**
     * Generates a services id from the given class.
     * 
     * @param \ReflectionClass $class 
     */
    protected function generateServiceId(\ReflectionClass $class)
    {
        return Inflector::className2ServiceId($class->getShortname(), $class->getNamespaceName());
    }
}
