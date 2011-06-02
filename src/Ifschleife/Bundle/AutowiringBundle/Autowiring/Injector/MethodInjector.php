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
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Parameter;

/**
 * MethodInjector
 *
 * @author joshi
 */
abstract class MethodInjector extends Injector
{
    /**
     * Guesses arguments size and types by analyzing the to-inject method
     * signature.
     * 
     * @param \ReflectionMethod $method
     * @return array $arguments
     */
    public function guessArgumentsForMethodSignature(\ReflectionMethod $method)
    {
        $is_optional = $this->hasAnnotation(self::ANNOTATION_OPTIONAL)
                ? $this->getAnnotation(self::ANNOTATION_OPTIONAL)->getIsOptional() : false;
        
        $is_strict   = $this->hasAnnotation(self::ANNOTATION_STRICT)
                ? $this->getAnnotation(self::ANNOTATION_STRICT)->getIsStrict() : true;
        
        $arguments = array();
        
        $parameters = $method->getParameters();
        
        $annotationsMap = new AnnotationsMap((array)$this->getAnnotation(self::ANNOTATION_INJECT)->value, $parameters);
        
        /* @var $parameter \ReflectionParameter */
        foreach ($parameters AS $i => $parameter)
        {
            if($annotationsMap->hasHint($i))
            {
                $resource_name = $annotationsMap->getResourceName($i);
                
                // NO MATCHING SERVICE PARAMETER FOUND, CHECK FOR SCALAR VALUE
                if($annotationsMap->getIsReference($i))
                {
                    try 
                    {
                        $arguments[] = $this->container->findDefinition($resource_name);
                    }
                    catch(\InvalidArgumentException $e)
                    {
                        throw new UnresolvedServiceException(sprintf('Reference %s for argument "$%s" on method "%s::%s"could not be resolved', $resource_name, $parameter->getName(), $method->getDeclaringClass()->getName(), $method->getName()), null, $e);
                    }
                }
                elseif($annotationsMap->getIsParameter($i))
                {
                    if($this->container->hasParameter($resource_name))
                    {
                        $arguments[] = new Parameter($resource_name);
                    }
                    else
                    {
                        throw new UnresolvedParameterException(sprintf('Parameter %s for argument "$%s" on method "%s::%s"could not be resolved', $resource_name, $parameter->getName(), $method->getDeclaringClass()->getName(), $method->getName()));
                    }
                }
                else
                {
                    $arguments[] = new Parameter($this->addParameter($resource_name));
                }
            }
            else
            {
                $type = $parameter->getClass();
                
                if(null === $type)
                {
                    //throw new UnresolvedServiceException(sprintf('Argument "$%s" at method signature "%s()" of class "%s" could not be resolved. Please provide a valid service id.', $parameter->getName(), $method->getName(), $method->getDeclaringClass()->getName()));
                    throw new TypenameMismatchException(sprintf('Type of argument "$%s" of method "%s::%s()" does not exist or cannot be resolved. Did you forgot to import it\'s namespace?', $parameter->getName(), $parameter->getDeclaringClass()->getName(), $parameter->getDeclaringFunction()->getName()));
                }
                else
                {
                    $service_id = $this->getClassNameMapper()->resolveService($type->getName(), Container::EXCEPTION_ON_INVALID_REFERENCE, true);

                    if (null === $service_id)
                    {
                        throw new UnresolvedServiceException(sprintf('Argument "$%s" of type "%s" at method signature "%s()" of class "%s" could not be auto-resolved. Please provide a valid service id.', $parameter->getName(), $type->getName(), $method->getName(), $method->getDeclaringClass()->getName()));
                    }

                    if (false === $service_id)
                    {
                        throw new UnresolvedServiceException(sprintf('Argument "$%s" of type "%s" at method signature "%s()" of class "%s" has been auto-resolved, but the matching services are ambiguous.', $parameter->getName(), $type->getName(), $method->getName(), $method->getDeclaringClass()->getName()));
                    }

                    $arguments[] = $this->createReference($service_id, $is_optional, $is_strict);
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
}
