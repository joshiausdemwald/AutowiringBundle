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

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Parameter;

use Doctrine\Common\Annotations\Reader;

use Ifschleife\Bundle\AutowiringBundle\Autowiring\ClassnameMapper;

/**
 * MethodInjector
 *
 * @author joshi
 */
abstract class MethodInjector extends Injector
{
    /**
     *
     * @var ClassnameMapper
     */
    protected $classNameMapper;
    
    /**
     * @var Boolean
     */
    protected $wireByType;
    
    public function __construct(ContainerBuilder $container, Reader $reader, ClassnameMapper $classnameMapper)
    {
        parent::__construct($container, $reader);
        
        $this->classnameMapper = $classnameMapper;
        
        $this->setWireByType(true);
    }
    
    /**
     * Set to true to enabled type wiring,
     * otherwise false.
     * 
     * @param boolean $wire_by_type 
     */
    public function setWireByType($wire_by_type)
    {
        $this->wireByType = $wire_by_type;
    }
    
    
    /**
     * Guesses arguments size and types by analyzing the to-inject method
     * signature.
     * 
     * @param \ReflectionMethod $method
     * @return array $arguments
     */
    public function guessArgumentsForMethodSignature(\ReflectionMethod $method)
    {
        $arguments = array();
        
        $parameters = $method->getParameters();
        
        $annotationsMap = new AnnotationsMap((array)$this->getAnnotation(self::ANNOTATION_INJECT)->value, $parameters);
        
        /* @var $parameter \ReflectionParameter */
        foreach ($parameters AS $i => $parameter)
        {
            if($annotationsMap->hasHint($i))
            {
                $is_optional = $annotationsMap->getIsOptional($i);

                $resource_name = $annotationsMap->getResourceName($i);
                
                // NO MATCHING SERVICE PARAMETER FOUND, CHECK FOR SCALAR VALUE
                if($annotationsMap->getIsReference($i))
                {
                    try 
                    {
                        $this->container->findDefinition($resource_name);
                    }
                    catch(\InvalidArgumentException $e)
                    {
                        throw new UnresolvedReferenceException(sprintf('Argument "$%s" on method "%s::%s()" could not be resolved: Service definition "%s" not found. Please provide a valid service id.', $parameter->getName(), $method->getDeclaringClass()->getName(), $method->getName(), $resource_name), null, $e);
                    }
                    
                    $arguments[] = $this->createReference($resource_name, $this->getBehaviour($parameter, $is_optional), true);

                }
                elseif($annotationsMap->getIsParameter($i))
                {
                    if($this->container->hasParameter($resource_name))
                    {
                        $arguments[] = new Parameter($resource_name);
                    }
                    elseif($is_optional)
                    {
                        // THROWS NonOptionalArgumentException IF ARGUMENT MAY NOT BE OPTIONAL, IS NULLABLE OR HAS DEFAULT VALUE
                        $this->getBehaviour($parameter, true);
                    }
                    else 
                    {
                        throw new UnresolvedParameterException(sprintf('Argument "$%s" on method "%s::%s()"could not be resolved: Container parameter "%s" not found. Please provide a valid parameter name.', $parameter->getName(), $method->getDeclaringClass()->getName(), $method->getName(), $resource_name));
                    }
                }
                else
                {
                    $arguments[] = new Parameter($this->addParameter($resource_name));
                }
            }
            elseif(true === $this->wireByType)
            {
                $type = null;
                
                try 
                {
                    $type = $parameter->getClass();
                }
                
                // Error resolving/autoloading class
                catch(\ReflectionException $e)
                {
                    throw new TypenameMismatchException(sprintf('Class of argument "$%s" at method signature of "%s::%s()" does not exist or could not be loaded. Probably a namespace typo?', $parameter->getName(), $parameter->getDeclaringClass()->getName(), $parameter->getDeclaringFunction()->getName()), null, $e);
                }
                
                if(null === $type)
                {
                    throw new MissingIdentifierException(sprintf('Argument "$%s" at method signature of "%s::%s()" cannot not be resolved without an identifier: Please provide a valid service id, or a parameter name, or a plain value.', $parameter->getName(), $method->getDeclaringClass()->getName(), $method->getName()));
                }
                else
                {   
                    $service_id = $this->classnameMapper->resolveService($type->getName());

                    if (null === $service_id)
                    {
                        throw new UnresolvedReferenceException(sprintf('Argument "$%s" at method signature of "%s::%s()" could not be resolved: There is no service that matches the arguments typename. Please provide a valid service id.', $parameter->getName(), $method->getDeclaringClass()->getName(), $method->getName(), $type->getName()));
                    }

                    if (false === $service_id)
                    {
                        throw new AmbiguousServiceReferenceException(sprintf('Argument "$%s" of type "%s" at method signature of "%s::%s()" could not be distinctly allocated: There is more than on services that rely on the given type. Please provide a valid, distinct service id.', $parameter->getName(), $type->getName(),  $method->getDeclaringClass()->getName(), $method->getName()));
                    }
                    
                    $arguments[] = $this->createReference($service_id, $this->getBehaviour($parameter), true);
                }
            }
        }
        return $arguments;
    }
    
    /**
     * Reads the method annotations and returns them as a flat array.
     * 
     * @param \Reflector $method 
     */
    protected function readAnnotations(\Reflector $method)
    {
       return $this->reader->getMethodAnnotations($method);
    }
    
    protected function getBehaviour(\ReflectionParameter $parameter, $is_optional = null)
    {
        if(null === $is_optional || $is_optional)
        {
            if($parameter->isOptional() || $parameter->isDefaultValueAvailable())
            {
                return Container::IGNORE_ON_INVALID_REFERENCE;
            }
            elseif($parameter->allowsNull())
            {
                return Container::NULL_ON_INVALID_REFERENCE;
            }
            
            // Provide a warning
            if($is_optional)
            {
                throw new NonOptionalArgumentException(sprintf('Injection for Argument "$%s" at signature of method "%s::%s()" must not defined beeing optional. Provide a type hint!', $parameter->getName(), $parameter->getDeclaringClass()->getName(), $parameter->getDeclaringFunction()->getName()));
            }
        }
        
        return Container::EXCEPTION_ON_INVALID_REFERENCE;
    }
}
