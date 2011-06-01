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
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Parameter;

use Ifschleife\Bundle\AutowiringBundle\Autowiring\Inflector;

/**
 * Description of PropertyInjector
 *
 * @author joshi
 */
class PropertyInjector extends Injector
{   
    protected function process(Definition $definition, \Reflector $property)
    {
        $is_optional = $this->hasAnnotation(self::ANNOTATION_OPTIONAL)
                ? $this->getAnnotation(self::ANNOTATION_OPTIONAL)->getIsOptional() : false;
        
        $is_strict   = $this->hasAnnotation(self::ANNOTATION_STRICT)
            ? $this->getAnnotation(self::ANNOTATION_STRICT)->getIsStrict() : true;

        /* @var $property \ReflectionProperty */
        if ($this->hasAnnotation(self::ANNOTATION_INJECT))
        {
            $di_hints = $this->getAnnotation(self::ANNOTATION_INJECT)->getHints();

            if (null === $di_hints || null === ($di_hint = array_pop($di_hints)))
            {
                throw new UnresolvedServiceException(sprintf('Property "$%s" of class "%s" cannot be injected. Please provide a valid service id.', $property->getName(), $class->getName()));
            }

            $inject = null;
            
            if ($this->container->findDefinition($di_hint))
            {
                $inject = $this->createReference($di_hint, $is_optional, $is_strict);
            }
            elseif($this->container->hasParameter($di_hint))
            {
                $inject = new Parameter($di_hint);
            }
            else
            {
                $inject = new Parameter($this->addParameter($di_hint));
            }

            $definition->setProperty($property->getName(), $inject);
        }

        // GUESS BY NAMING CONVENTION
        else
        {
            if ('Service' === substr($property->getName(), -7, 7))
            {
                // changed: from findDefinition to getDefinition
                $service_id = Inflector::propertyName2ServiceId($property->getName());

                if($this->container->findDefinition($service_id))
                {
                    $definition->setProperty($property->getName(), $this->createReference($service_id, $is_optional, $is_strict));
                }
            }
        }
    }
    
    /**
     * Reads the method annotations and returns them as a flat array.
     * 
     * @param \Reflector $method 
     */
    protected function readAnnotations(\Reflector $property)
    {
       return $this->reader->getPropertyAnnotations($property);
    }
}