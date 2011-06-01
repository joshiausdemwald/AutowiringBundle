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
     * @var array
     */
    private $classMap;
    
    /**
     * @var array
     */
    private $aliasMap;
    
    /**
     * @var array<ReflectionClass>
     */
    private $classes;
    
    /**
     * @var array<String, Parameter>
     */
    private $parametersMap;
    
    /**
     * Guesses arguments size and types by analyzing the to-inject method
     * signature. The $di_hints array contains information about primitive
     * types (may be di parameters or primitive values) and complex types
     * that e.g. are defined ambiguous in the DIC.
     * 
     * @param \ReflectionMethod $method
     * @param array $di_hints
     * @return array $arguments
     */
    public function guessArgumentsForMethodSignature(\ReflectionMethod $method)
    {
        $signature = $method->getParameters();

        $di_hints = $this->mapDIHints($signature, $this->getAnnotation(self::ANNOTATION_INJECT)->getHints());
        
        $is_optional = $this->hasAnnotation(self::ANNOTATION_OPTIONAL)
                ? $this->getAnnotation(self::ANNOTATION_OPTIONAL)->getIsOptional() : false;
        
        $is_strict   = $this->hasAnnotation(self::ANNOTATION_STRICT)
                ? $this->getAnnotation(self::ANNOTATION_STRICT)->getIsStrict() : true;
        
        $arguments = array();
        
        
        
        for ($i = 0; $signature_size = count($signature), $i < $signature_size; $i++)
        {
            /* @var $parameter \ReflectionParameter */
            $parameter = $signature[$i];

            // NON-OBJECT PARAMETER
            if (null === ($type = $parameter->getClass()))
            {
                // WIRE PARAMETER
                if (null === $di_hints || ! array_key_exists($i, $di_hints))
                {
                    throw new UnresolvedServiceException(sprintf('Argument "$%s" at method signature "%s()" of class "%s" could not be resolved. Please provide a valid service id.', $parameter->getName(), $method->getName(), $method->getDeclaringClass()->getName()));
                }

                $di_hint = $di_hints[$i];
                
                // NO MATCHING SERVICE PARAMETER FOUND, CHECK FOR SCALAR VALUE
                if (is_string($di_hint))
                {
                    if($this->container->has($di_hint))
                    {
                        $arguments[] = $this->createReference($di_hint, $is_optional, $is_strict);
                    }
                    elseif($this->container->hasParameter($di_hint))
                    {
                        $arguments[] = new Parameter($di_hint);
                    }
                    else
                    {
                        $arguments[] = new Parameter($this->addParameter($di_hint));
                    }
                }
                else
                {
                    $arguments[] = new Parameter($this->addParameter($di_hint));
                }
            }
            else
            {
                if (null !== $di_hints && array_key_exists($i, $di_hints))
                {
                    $arguments[] = $this->createReference($di_hints[$i], $is_optional, $is_strict);
                }
                else
                {
                    $service_id = $this->getClassNameMapper()->resolveService($type->getName(), Container::EXCEPTION_ON_INVALID_REFERENCE, true);

                    if (null === $reference)
                    {
                        throw new UnresolvedServiceException(sprintf('Argument "$%s" of type "%s" at method signature "%s()" of class "%s" could not be auto-resolved. Please provide a valid service id.', $parameter->getName(), $type->getName(), $method->getName(), $method->getDeclaringClass()->getName()));
                    }
                    
                    if (false === $reference)
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
    
    /**
     * Orders and maps the di hints provided in any Inject() annotation
     * to the proper method names.
     * 
     * @param array $signature
     * @param array $di_hints
     * @return array 
     */
    protected function mapDIHints(array $signature, array $di_hints)
    {
        $output = array();

        $length = count($signature);

        for ($i = 0; $i < $length; $i++)
        {
            /* @var $parameter \ReflectionParameter */
            $parameter = $signature[$i];

            $name = $parameter->getName();

            if (isset($di_hints[$name]))
            {
                $output[$i] = $di_hints[$name];
            }
            elseif (isset($di_hints[$i]))
            {
                $output[$i] = $di_hints[$i];
            }
            elseif ($parameter->isDefaultValueAvailable())
            {
                $output[$i] = $parameter->getDefaultValue();
            }
        }

        return $output;
    }
}
