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
            $annotationMap = new AnnotationsMap((array)$this->getAnnotation(self::ANNOTATION_INJECT)->value, array($property));
            
            if (! $annotationMap->hasHint(0))
            {
                throw new UnresolvedPropertyException(sprintf('Property "$%s" of class "%s" cannot be injected. Please provide a valid service id.', $property->getName(), $property->getDeclaringClass()->getName()));
            }

            $inject = null;
            
            $resource_name = $annotationMap->getResourceName(0);
            
            if($annotationMap->getIsReference(0))
            {
                try 
                {
                    $this->container->findDefinition($resource_name);
                }
                catch(\InvalidArgumentException $e)
                {
                    throw new UnresolvedServiceException(sprintf('Property "$%s" of class "%s" cannot be injected because reference "%s" could not be resolved. Please provide a valid service id.', $property->getName(), $property->getDeclaringClass()->getName(), $di_hint), null, $e);
                }
                $inject = $this->createReference($resource_name, $is_optional, $is_strict);
            }
            elseif($annotationMap->getIsParameter(0))
            {
                if(! $this->container->hasParameter($resource_name))
                {
                    throw new UnresolvedParamterException(sprintf('Property "$%s" of class "%s" cannot be injected because parameter "%s" could not be resolved. Please provide a valid parameter name.', $property->getName(), $property->getDeclaringClass()->getName(), $di_hint), null, $e);
                }
                $inject = new Parameter($resource_name);
            }
            else
            {
                $inject = new Parameter($this->addParameter($resource_name));
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

                try 
                {
                    $this->container->findDefinition($service_id);
                    
                    $definition->setProperty($property->getName(), $this->createReference($service_id, $is_optional, $is_strict));
                }
                catch(\InvalidArgumentException $e)
                {
                    throw new UnresolvedPropertyException(sprintf('Instance property "%s::$%s" on service could not be resolved.', $property->getDeclaringClass()->getName(), $property->getName()), null, $e);
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