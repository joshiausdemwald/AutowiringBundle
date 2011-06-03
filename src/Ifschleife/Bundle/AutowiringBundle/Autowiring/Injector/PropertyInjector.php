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

         $inject = null;
        
        /* @var $property \ReflectionProperty */
        if ($this->hasAnnotation(self::ANNOTATION_INJECT))
        {
            $annotationMap = new AnnotationsMap((array)$this->getAnnotation(self::ANNOTATION_INJECT)->value, array($property));
            
            if (! $annotationMap->hasHint(0))
            {
                throw new MissingIdentifierException(sprintf('Property "%s::$%s" cannot not be resolved without an identifier. Please provide a valid service id, or a parameter name, or a plain value.', $property->getDeclaringClass()->getName(), $property->getName()));
            }

            $resource_name = $annotationMap->getResourceName(0);
            
            if($annotationMap->getIsReference(0))
            {
                try 
                {
                    $this->container->findDefinition($resource_name);
                }
                catch(\InvalidArgumentException $e)
                {
                    throw new UnresolvedReferenceException(sprintf('Instance property "%s::$%s" could not be resolved: Service definition "%s" not found. Please provide a valid service id.', $property->getDeclaringClass()->getName(), $property->getName(), $resource_name), null, $e);
                }
                
                $inject = $this->createReference($resource_name, $is_optional, $is_strict);
            }
            elseif($annotationMap->getIsParameter(0))
            {
                if(! $this->container->hasParameter($resource_name))
                {
                    throw new UnresolvedParamterException(sprintf('Instance property "%s::$%s" could not be resolved: Container parameter "%s" not found. Please provide a valid parameter name.', $property->getDeclaringClass()->getName(), $property->getName(), $resouce_name));
                }
                
                $inject = new Parameter($resource_name);
            }
            
            // CREATE NEW DI-PARAMETER
            else
            {
                $inject = new Parameter($this->addParameter($resource_name));
            }
        }

        // GUESS BY NAMING CONVENTION
        else
        {
            if ('Service' === substr($property->getName(), -7, 7))
            {
                $service_id = Inflector::propertyName2ServiceId($property->getName());

                try 
                {
                    $this->container->findDefinition($service_id);
                }
                catch(\InvalidArgumentException $e)
                {
                    throw new UnresolvedReferenceException(sprintf('Instance property "%s::$%s" could not be resolved: Service definition "%s" not found. Please provide a valid service id.', $property->getDeclaringClass()->getName(), $property->getName(), $service_id), null, $e);
                }
                
                $inject = $this->createReference($service_id, $is_optional, $is_strict);
            }
            elseif('Parameter' === substr($property->getName(), -9, 9))
            {
                $parameter_name = Inflector::propertyName2ParameterName($property->getName());
                
                if( ! $this->container->hasParameter($parameter_name))
                {
                    throw new UnresolvedParameterException(sprintf('Instance property "%s::$%s" could not be resolved: Container parameter "%s" not found. Please provide a valid parameter name.', $property->getDeclaringClass(), $property->getName(), $parameter_name));
                }
                
                $inject = new Parameter($parameter_name);
            }
        }
        
        // INJECT IF RESOLVED
        if(null !== $inject)
        {
            $definition->setProperty($property->getName(), $inject);
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