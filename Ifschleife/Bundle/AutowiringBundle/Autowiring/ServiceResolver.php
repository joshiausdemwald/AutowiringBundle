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
use Doctrine\Common\Annotations\Annotation;
use Ifschleife\Bundle\AutowiringBundle\Annotations\Inject;

/**
 * ServiceResolver
 * 
 * This class maps @Inject class annotations in each service class to primitive
 * values or services.
 * 
 * @author joshi
 */
class ServiceResolver
{
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
	 * @var array<Definition>
	 */
	private $definitions;
	
	/**
	 * @var array<Alias>
	 */
	private $aliases;
	
	/**
	 * @var array
	 */
	private $serviceIds;
	
	/**
	 * @var array<ReflectionClass>
	 */
	private $classes;
	
	/**
	 * @var ParameterBag
	 */
	private $parameters;
	
	/**
	 * @var array<String, Parameter>
	 */
	private $parametersMap;
	
	/**
	 * Constructor
	 * 
	 * @param ContainerBuilder $container: The container builder
	 */
	public function __construct(ContainerBuilder $container)
	{
		$this->container = $container; 
		
		$this->reader = new \Doctrine\Common\Annotations\AnnotationReader();
	}
	
	public function initialize()
	{
		$this->definitions = $this->container->getDefinitions();
		
		$this->aliases = $this->container->getAliases();
		
		$this->serviceIds = $this->container->getServiceIds();
		
		$this->parameters = $this->container->getParameterBag();
		
		$this->classMap = array();
		
		$this->aliasMap = array();
		
		$this->classes = array();
		
		$this->parametersMap = array();
		
		$this->resolveServices();

		$this->resolveAliases();
		
		$this->extendDefinitions();
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
							
		if(array_key_exists($typename, $this->classMap))
		{
			$service_id = $this->classMap[$typename];
		}
		elseif(array_key_exists($typename, $this->aliasMap))
		{
			$service_id = $this->aliasMap[$typename];
		}
		if(null === $service_id)
		{
			return null;
		}
		return new Reference($service_id, $invalidBehavior, $strict);
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
	public function resolveParameter($callout)
	{
		if(isset($this->parametersMap[$callout]))
		{
			return $this->parametersMap[$callout];
		}
		
		$this->parametersMap[$callout] = $retVal = $this->parameters->resolveValue($callout);
		
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
		foreach($this->classMap AS $classname => $id)
		{
			$definition = $this->getDefinition($id);
			
			$class = $this->classes[$id];
			
			$this->extendConstructorInjections($definition, $class);
			
			$this->extendMethodCalls($definition, $class);
			
			$this->extendPropertyInjections($definition, $class);
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
		$id = 'ifschleife.autowiring.' . str_replace('.', '_', uniqid(mt_rand(), true));
		
		$this->container->setParameter($id, $value);
		
		return $id;
	}
	
	private function extendConstructorInjections(Definition $definition, \ReflectionClass $class)
	{
		$constructor = $class->getConstructor();
		
		if(null !== $constructor && null !== ($annotation = $this->getMethodAnnotation($constructor)))
		{
			$arguments = $this->guessArgumentsForMethodSignature($constructor, $class, $annotation->getHints());
			
			if(count($definition->getArguments()) > 0)
			{
				throw new ArgumentsAlreadyDefinedException(sprintf('Constructor "%s()" on class "%s" has already been injected by the dependency injection container or extension configuration. Please check your container´s config files.', $method->getName(), $class->getName()));
			}
			
			$definition->setArguments($arguments);
		}
	}
	
	/**
	 * 
	 * @param type $definition
	 * @param type $class 
	 */
	private function extendPropertyInjections(Definition $definition, \ReflectionClass $class)
	{
		foreach($class->getProperties() AS $property)
		{
			/* @var $property \ReflectionProperty */
			if(null === ($annotation = $this->getPropertyAnnotation($property)))
			{
				if('Service' === substr($property->getName(), -7, 7))
				{
					$service_definition = $this->getDefinition($service_id = Inflector::propertyName2ServiceId($property->getName()));
					
					if(null !== $service_definition)
					{
						$definition->setProperty($property->getName(), new Reference($service_id, Container::EXCEPTION_ON_INVALID_REFERENCE, true));
					}
				}
			}
			else
			{
				$di_hints = $annotation->getHints();
				
				if(null === $di_hints || null === ($di_hint = array_pop($di_hints)))
				{
					throw new UnresolvedServiceException(sprintf('Property "$%s" of class "%s" cannot be injected. Please provide a valid service id.', $property->getName(), $class->getName()));
				}
				
				$service_definition =  $this->getDefinition($di_hint);
				
				$property = null;
				
				if(null === $service_definition)
				{
					$property = new Parameter($this->addParameter($di_hint));
				}
				else
				{
					$property = new Reference($di_hint, Container::EXCEPTION_ON_INVALID_REFERENCE, true);
				}
				
				$definition->setProperty($property->getName(), $property);
			}
		}
	}
	
	private function extendMethodCalls(Definition $definition, \ReflectionClass $class)
	{		
		foreach($class->getMethods() AS $method)
		{		
			/* @var $method \ReflectionMethod */
			if(null !== ($annotation = $this->getMethodAnnotation($method)))
			{
				$arguments = $this->guessArgumentsForMethodSignature($method, $class, $annotation->getHints());
				
				if($definition->hasMethodCall($method->getName()))
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
	 *
	 * @param \ReflectionMethod $method
	 * @param \ReflectionClass $class
	 * @param array $di_hints
	 * @return array $arguments
	 */
	private function guessArgumentsForMethodSignature(\ReflectionMethod $method, \ReflectionClass $class, array $di_hints = null)
	{
		$signature = $method->getParameters();

		$signature_size = count($signature);

		$di_hints = $di_hints === null ? null : $this->mapDIHints($signature, $di_hints);

		$arguments = array();

		for($i = 0; $i < $signature_size; $i++)
		{
			/* @var $parameter \ReflectionParameter */
			$parameter = $signature[$i];

			// NON-OBJECT PARAMETER
			if(null === ($type = $parameter->getClass()))
			{
				// WIRE PARAMETER
				if(null === $di_hints || ! array_key_exists($i, $di_hints))
				{
					throw new UnresolvedServiceException(sprintf('Argument "$%s" at method signature "%s()" of class "%s" could not be resolved. Please provide a valid service id.', $parameter->getName(), $method->getName(), $class->getName()));
				}

				$di_hint = $di_hints[$i];

				// NO MATCHING SERVICE PARAMETER FOUND, CHECK FOR SCALAR VALUE
				if(is_string($di_hint) && $this->parameters->has($di_hint))
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
				if(null !== $di_hints && array_key_exists($i, $di_hints))
				{
					$arguments[] = new Reference($di_hints[$i], Container::EXCEPTION_ON_INVALID_REFERENCE, true);
				}
				else
				{
					$reference = $this->resolveService($type->getName(), Container::EXCEPTION_ON_INVALID_REFERENCE, true);
					
					if(null === $reference)
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
		
		for($i = 0; $i < $length; $i++)
		{
			/* @var $parameter \ReflectionParameter */
			$parameter = $signature[$i];
			
			$name = $parameter->getName();
			
			if(isset($di_hints[$name]))
			{
				$output[$i] = $di_hints[$name];
			}
			elseif(isset($di_hints[$i]))
			{
				$output[$i] = $di_hints[$i];
			}
			elseif($parameter->isDefaultValueAvailable())
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
		if(null !== ($annotation = $this->reader->getMethodAnnotation($method, 'Ifschleife\Bundle\AutowiringBundle\Annotations\Inject')))
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
		if(null !== ($annotation = $this->reader->getPropertyAnnotation($property, 'Ifschleife\Bundle\AutowiringBundle\Annotations\Inject')))
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
		foreach($this->definitions AS $id => $definition)
		{
			if(! $definition->isAbstract() && $definition->isPublic())
			{
				$classname = $this->resolveClassname($definition);
				
				if(null !== $classname && ! array_key_exists($classname, $this->classMap))
				{
					$this->classes[$id] = new \ReflectionClass($classname);
							
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
		foreach($this->aliases AS $name => $alias_definition)
		{
			if($alias_definition->isPublic())
			{
				/* @var $alias_definition \Symfony\Component\DependencyInjection\Alias */
				$target_definition = $this->container->findDefinition($name);

				if(null !== $target_definition)
				{
					$classname = $this->resolveClassname($target_definition);
					
					if( ! array_key_exists($classname, $this->aliasMap))
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
	 *							    implementation) for the definition.
	 */
	private function resolveClassname(Definition $definition)
	{
		$classname = $definition->getClass();
		
		// MAY HAVE PARENT DEF
		if(null === $classname)
		{
			if(method_exists($definition, 'getParent'))
			{
				$parent_id = $definition->getParent();

				if(null === $parent_id)
				{
					return null;
				}

				$parent = $this->getDefinition($parent_id);

				if(null == $parent)
				{
					return null;
				}

				return $this->resolveClassname($parent);
			}
			
			elseif(null !== ($factory_class = $definition->getFactoryClass()))
			{
				return $this->resolveClassname($factory_class);
			}
		}
		
		return $this->resolveParameter($classname);
	}
	
	/**
     * Returns a matching definition for the given id.
	 *
	 * @param string $id 
	 * @return Definition $definition or null if no definition exists for the $Id
	 */
	private function getDefinition($id)
	{
		return isset($this->definitions[$id]) ? $this->definitions[$id] : null;
	}
}
