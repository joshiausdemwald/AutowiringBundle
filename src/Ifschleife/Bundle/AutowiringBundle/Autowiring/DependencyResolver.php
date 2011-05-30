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

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Doctrine\Common\Annotations\Reader;
use Ifschleife\Bundle\AutowiringBundle\Annotation\AnnotationReader;
use Ifschleife\Bundle\AutowiringBundle\Annotations\Inject;

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
    const ANNOTATION_INJECT     = 1;
    const ANNOTATION_OPTIONAL   = 2;
    const ANNOTATION_STRICT     = 4;
    const ANNOTATION_SERVICE    = 8;
    
    /**
     * @var ContainerBuilder
     */
    private $container;
    
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
     * @var boolean
     */
    private $isInitialized;
    
    /**
     * @var Reader
     */
    private $reader;
    
    /**
     * Constructor
     * 
     * @param ContainerBuilder $container: The container builder
     */
    public function __construct(ContainerBuilder $container, Reader $reader = null)
    {
        $this->container = $container;
        
        $this->reader = null === $reader ? new AnnotationReader() : $reader;
        
        $this->isInitialized = false;
    }

    /**
     * Initializes all instance variables.
     * 
     * @return void
     */
    public function initialize()
    {
        $this->classMap = array();

        $this->aliasMap = array();

        $this->classes = array();

        $this->parametersMap = array();
        
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
        
        $this->resolveServices();

        $this->resolveAliases();

        $this->extendDefinitions();
        
        $this->isInitialized = false;
    }

    /**
     * Returns a reference to the first matching service id for the given class name.
     * 
     * @param string $typename: A classname including namespace in common PHP syntax
     * @param integer $invalidBehaviour: One of the ContainerInterface::*_ON_INVALID_REFERENCE constants.
     * @param boolean $strict: Sets how the reference is validated
     * 
     * @return Reference $reference: A Reference instance for first matching service id of the given type
     */
    public function resolveService($typename, $invalidBehavior = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $strict = true)
    {
        $service_id = null;

        if (array_key_exists($typename, $this->classMap))
        {
            $service_id = $this->classMap[$typename];
        }
        elseif (array_key_exists($typename, $this->aliasMap))
        {
            $service_id = $this->aliasMap[$typename];
        }
        if (null === $service_id)
        {
            return null;
        }
        return new Reference($service_id, $invalidBehavior, $strict);
    }
    
    /**
     * Returns a single parameter which can be a concrete value (string) or
     * a placeholder like "%service.x.y.class_name%". If a placeholder is given,
     * it´s first matching value will be returned.
     * 
     * @see resolveClassname()
     * @see resolveParameter()
     * @param string $callout: The callout string.
     *
     * @return mixed $parameter: The parameter or null if callout does not match to any.
     */
    public function getParameter($callout)
    {
        if (isset($this->parametersMap[$callout]))
        {
            return $this->parametersMap[$callout];
        }
        
        return $this->resolveParameter($callout);
    }
    
    /**
     * Resolves a single parameter which can be a concrete value (string) or a
     * placeholder like "%service.x.y.class_name%". If a placeholder is given,
     * it´s first matching value will be returned.
     * 
     * @see resolveClassname()
     * @param string $callout
     * 
     * @return mixed $parameter: The parameter or null if callout does not match to anyone.
     */
    private function resolveParameter($callout)
    {
        $this->parametersMap[$callout] = $retVal = $this->container->getParameterBag()->resolveValue($callout);

        return $retVal;
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
        foreach ($this->classes AS $class)
        {
            $service_id = $this->classMap[$class->getName()];
            
            // AMBIGUOUS CLASSNAMES ARE NOT ALLOWED TO BE PROCESSED ...
            if(false !== $service_id)
            {
                $definition = $this->getDefinition($service_id);

                $this->extendConstructorInjections($definition, $class);

                $this->extendMethodCalls($definition, $class);

                $this->extendPropertyInjections($definition, $class);
            }
        }
    }

    /**
     * Adds a new parameter to the DIC, identified by
     * a unique id.
     * 
     * @param type $value 
     * @return Id: A unique parameter id
     */
    private function addParameter($value)
    {
        static $count = 1;
        
        static $id;
        
        if(null === $id)
        {
            $id = md5_file(__FILE__);
        }

        $service_id = $id . '_' . $count;
        
        $this->container->setParameter($id, $value);
        
        $count ++;
        
        return $id;
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
            $annotations = $this->getAnnotations($constructor);
            
            if(array_key_exists(self::ANNOTATION_INJECT, $annotations))
            {
                $arguments = $this->guessArgumentsForMethodSignature($constructor, $class, $annotations);
                
                if (count($definition->getArguments()) > 0)
                {
                    throw new ArgumentsAlreadyDefinedException(sprintf('Constructor "%s()" on class "%s" has already been injected by the dependency injection container or extension configuration. Please check your container´s config files.', $method->getName(), $class->getName()));
                }

                $definition->setArguments($arguments);
            }
        }
    }

    /**
     * Adds property injections to the DIC based on annotations.
     * 
     * @param Definition $definition
     * @param \ReflectionClass $class 
     */
    private function extendPropertyInjections(Definition $definition, \ReflectionClass $class)
    {
        foreach ($class->getProperties() AS $property)
        {
            $annotations = $this->getAnnotations($property);
            
            $is_optional = array_key_exists(self::ANNOTATION_OPTIONAL, $annotations) 
                ? $annotations[self::ANNOTATION_OPTIONAL]->getIsOptional() : false;
        
            $is_strict   = array_key_exists(self::ANNOTATION_STRICT, $annotations)
                ? $annotations[self::ANNOTATION_STRICT]->getIsStrict() : true;
            
            /* @var $property \ReflectionProperty */
            if (array_key_exists(self::ANNOTATION_INJECT, $annotations))
            {
                $di_hints = $annotations[self::ANNOTATION_INJECT]->getHints();

                if (null === $di_hints || null === ($di_hint = array_pop($di_hints)))
                {
                    throw new UnresolvedServiceException(sprintf('Property "$%s" of class "%s" cannot be injected. Please provide a valid service id.', $property->getName(), $class->getName()));
                }

                $inject = null;
                
                if ($this->container->has($di_hint))
                {
                    $inject = new Reference($di_hint, Container::EXCEPTION_ON_INVALID_REFERENCE, true);
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
                    $service_definition = $this->container->findDefinition($service_id = Inflector::propertyName2ServiceId($property->getName()));
                    
                    if (null !== $service_definition)
                    {
                        $definition->setProperty($property->getName(), new Reference($service_id, Container::EXCEPTION_ON_INVALID_REFERENCE, true));
                    }
                }
            }
        }
    }
    
    /**
     * Adds method calls to the DIC configured by class annotations.
     * 
     * @todo Optimize getMethods() call by using \ReflectionMethod constants
     * @param Definition $definition
     * @param \ReflectionClass $class 
     */
    private function extendMethodCalls(Definition $definition, \ReflectionClass $class)
    {
        foreach ($class->getMethods() AS $method)
        {
            if($method->isConstructor() || $method->isDestructor() || $method->isStatic() || $method->isAbstract()) continue;
            
            $annotations = $this->getAnnotations($method);
            
            /* @var $method \ReflectionMethod */
            if (array_key_exists(self::ANNOTATION_INJECT, $annotations))
            {
                $arguments = $this->guessArgumentsForMethodSignature($method, $class, $annotations);

                if ($definition->hasMethodCall($method->getName()))
                {
                    throw new MethodCallAlreadyDefinedException(sprintf('Method Call "%s()" on class "%s" has already been defined by the dependency injection container or extension configuration. Please check your container´s config files.', $method->getName(), $class->getName()));
                }
                else
                {
                    $definition->addMethodCall($method->getName(), $arguments);
                }
            }
        }
    }

    /**
     * Guesses arguments size and types by analyzing the to-inject method
     * signature. The $di_hints array contains information about primitive
     * types (may be di parameters or primitive values) and complex types
     * that e.g. are defined ambiguous in the DIC.
     * 
     * @param \ReflectionMethod $method
     * @param \ReflectionClass $class
     * @param array $di_hints
     * @return array $arguments
     */
    private function guessArgumentsForMethodSignature(\ReflectionMethod $method, \ReflectionClass $class, array $annotations)
    {
        $signature = $method->getParameters();

        $signature_size = count($signature);
        
        $di_hints = $this->mapDIHints($signature, $annotations[self::ANNOTATION_INJECT]->getHints());
        
        $is_optional = array_key_exists(self::ANNOTATION_OPTIONAL, $annotations) 
                ? $annotations[self::ANNOTATION_OPTIONAL]->getIsOptional() : false;
        
        $is_strict   = array_key_exists(self::ANNOTATION_STRICT, $annotations)
                ? $annotations[self::ANNOTATION_STRICT]->getIsStrict() : true;
        
        $arguments = array();

        for ($i = 0; $i < $signature_size; $i++)
        {
            /* @var $parameter \ReflectionParameter */
            $parameter = $signature[$i];

            // NON-OBJECT PARAMETER
            if (null === ($type = $parameter->getClass()))
            {
                // WIRE PARAMETER
                if (null === $di_hints || ! array_key_exists($i, $di_hints))
                {
                    throw new UnresolvedServiceException(sprintf('Argument "$%s" at method signature "%s()" of class "%s" could not be resolved. Please provide a valid service id.', $parameter->getName(), $method->getName(), $class->getName()));
                }

                $di_hint = $di_hints[$i];
                
                // NO MATCHING SERVICE PARAMETER FOUND, CHECK FOR SCALAR VALUE
                if (is_string($di_hint))
                {
                    if($this->container->has($di_hint))
                    {
                        $arguments[] = new Reference($di_hint, Container::EXCEPTION_ON_INVALID_REFERENCE, true);
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
                    $arguments[] = new Reference($di_hints[$i], Container::EXCEPTION_ON_INVALID_REFERENCE, true);
                }
                else
                {
                    $reference = $this->resolveService($type->getName(), Container::EXCEPTION_ON_INVALID_REFERENCE, true);

                    if (null === $reference)
                    {
                        throw new UnresolvedServiceException(sprintf('Argument "$%s" of type "%s" at method signature "%s()" of class "%s" could not be auto-resolved. Please provide a valid service id.', $parameter->getName(), $type->getName(), $method->getName(), $class->getName()));
                    }

                    $arguments[] = $reference;
                }
            }
        }

        return $arguments;
    }

    /**
     * Orders and maps the di hints provided in any Inject() annotation
     * to the proper method names.
     * 
     * @param array $signature
     * @param array $di_hints
     * @return array 
     */
    private function mapDIHints(array $signature, array $di_hints)
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

    /**
     * Returns a @Inject annotation instance for the given ReflectionClass
     * 
     * @param \ReflectionClass $class
     * @return Inject $annotation
     */
    private function getMethodAnnotation(\ReflectionMethod $method)
    {
        if (null !== ($annotation = $this->reader->getMethodAnnotation($method, 'Ifschleife\Bundle\AutowiringBundle\Annotations\Inject')))
        {
            return $annotation;
        }

        return null;
    }

    /**
     * Returns a @Inject annotation instance for the given ReflectionProperty
     * 
     * @param \ReflectionClass $class
     * @return Inject $annotation
     */
    private function getPropertyAnnotation(\ReflectionProperty $property)
    {
        if (null !== ($annotation = $this->reader->getPropertyAnnotation($property, 'Ifschleife\Bundle\AutowiringBundle\Annotations\Inject')))
        {
            return $annotation;
        }

        return null;
    }

    /**
     * Called by initialize() to resolve all services mapped by it´s classname.
     * 
     * @see initialize()
     * @return void
     */
    private function resolveServices()
    {
        foreach ($this->container->getDefinitions() AS $id => $definition)
        {
            if ( ! $definition->isSynthetic() && ! $definition->isAbstract() && $definition->isPublic())
            {
                // Transform %service_class% to concrete class, regarding parent
                // definitions.
                $classname = $this->resolveClassname($definition);
                
                // AMBIGUOUS SERVICE, FLAG AS INVALID FOR LOOKUP
                if(array_key_exists($classname, $this->classMap))
                {
                    $this->classMap[$classname] = false;
                }
                else
                {
                    $class = new \ReflectionClass($classname);
                    
                    $this->classes[$id] = $class;
                    
                    $this->classMap[$classname] = $id;
                }
            }
        }
    }

    /**
     * Called by initialize() to resolve all aliases mapped by it´s classname.
     * 
     * @see initialize()
     * @return void
     */
    private function resolveAliases()
    {
        foreach ($this->container->getAliases() AS $name => $alias_definition)
        {
            if ($alias_definition->isPublic())
            {
                /* @var $alias_definition \Symfony\Component\DependencyInjection\Alias */
                $target_definition = $this->container->findDefinition($name);
                
                if (null === $target_definition)
                {
                    throw new DefinitionNotFoundException(sprintf('Service definition for alias "%s" could not be resolved.', $name));
                }
                
                // SYNTHETIC DEFINITIONS HAS NO CLASSNAME
                if( ! $target_definition->isSynthetic())
                {
                    // transform %service_class% to concrete class, regarding parent
                    // definitions.
                    $classname = $this->resolveClassname($target_definition);

                    // AMBIGUOUS SERVICE, FLAG AS INVALID FOR LOOKUP
                    if (array_key_exists($classname, $this->aliasMap))
                    {
                        $this->aliasMap[$classname] = false;
                    }
                    else
                    {
                        $this->aliasMap[$classname] = $name;
                    }
                }
            }
        }
    }

    /**
     * Resolves the classname of a definition. If the given definition has no 
     * classname, it´s parent definitions will be searched.
     * Called by resolveServices() and resolveAliases() methods.
     * 
     * @see resolveServices()
     * @see resolveAliases()
     * @param Definition $definition
     * @return string $class_name: The name of the next matching class (concrete 
     * 							    implementation) for the definition.
     */
    private function resolveClassname(Definition $definition)
    {
        $classname = $definition->getClass();
        
        // MAY HAVE PARENT DEF
        if (null === $classname)
        {
            if (method_exists($definition, 'getParent'))
            {
                $parent_id = $definition->getParent();

                if (null === $parent_id)
                {
                    return null;
                }

                $parent = $this->getDefinition($parent_id);

                if (null == $parent)
                {
                    return null;
                }

                return $this->resolveClassname($parent);
            }
            elseif (null !== ($factory_class = $definition->getFactoryClass()))
            {
                return $this->resolveClassname($factory_class);
            }
        }
        
        // Returns the concrete classname instead of e.g. "%service_class%"
        return $this->getParameter($classname);
    }

    /**
     * Returns a matching definition for the given id.
     *
     * @param string $id 
     * @return Definition $definition or null if no definition exists for the $Id
     */
    private function getDefinition($id)
    {
        return $this->container->hasDefinition($id) ? $this->container->getDefinition($id) : null;
    }
    
    /**
     * Maps the autowiring special annotations of methods, properties and classes
     * to an array with string keys.
     * 
     * @param \Reflector $reflector
     * @return type 
     */
    public function getAnnotations(\Reflector $reflector)
    {
        return static::getAnnotationsStatic($reflector, $this->reader);
    }
    
    /**
     * Maps the autowiring special annotations of methods, properties and classes
     * to an array with string keys.
     * 
     * @param \Reflector $reflector
     * @param Reader $reader
     * @return type 
     */
    public static function getAnnotationsStatic(\Reflector $reflector, Reader $reader = null)
    {
        if(null === $reader)
        {
            $reader = new AnnotationReader;
        }
        
        $annotations = null;
        
        if($reflector instanceof \ReflectionClass)
        {
            $annotations = $reader->getClassAnnotations($reflector);
        }
        elseif($reflector instanceof \ReflectionMethod)
        {
            $annotations = $reader->getMethodAnnotations($reflector);
        }
        elseif($reflector instanceof \ReflectionProperty)
        {
            $annotations = $reader->getPropertyAnnotations($reflector);
        }
        
        if(null !== $annotations)
        {
            $retVal = array();
            
            foreach($annotations AS $annotation)
            {
                switch(get_class($annotation))
                {
                    case 'Ifschleife\Bundle\AutowiringBundle\Annotations\Inject':
                        $retVal[self::ANNOTATION_INJECT] = $annotation;
                        continue(2);
                    case 'Ifschleife\Bundle\AutowiringBundle\Annotations\Optional':
                        $retVal[self::ANNOTATION_OPTIONAL] = $annotation;
                        continue(2);
                    case 'Ifschleife\Bundle\AutowiringBundle\Annotations\Strict':
                        $retVal[self::ANNOTATION_STRICT] = $annotation;
                        continue(2);
                    case 'Ifschleife\Bundle\AutowiringBundle\Annotations\Service':
                        $retVal[self::ANNOTATION_SERVICE] = $annotation;
                        continue(2);
                }
            }
            
            return $retVal;
        }
        
        return null;
    }
}
