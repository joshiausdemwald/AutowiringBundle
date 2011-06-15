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
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * ClassnameMapper
 *
 * @author joshi
 */
class ClassnameMapper
{
    /**
     * @var Container
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
     * Constructor.
     * 
     * @param Container $container 
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        
        $this->classMap = array();

        $this->aliasMap = array();

        $this->classes = array();

        $this->parametersMap = array();
        
        $this->collectServices();

        $this->collectAliases();
    }
    
    /**
     * @return array $classes: The found classes for each service
     */
    public function getClasses()
    {
        return $this->classes;
    }
    
    /**
     * Returns the corresponding service id for a classname.
     * If service id is ambiguous, false will be returned.
     * 
     * @param string $classname 
     */
    public function getServiceId($classname)
    {
        return $this->classMap[$classname];
    }
    
    /**
     * Called by initialize() to resolve all services mapped by it´s classname.
     * 
     * @see initialize()
     * @return void
     */
    private function collectServices()
    {
        foreach ($this->container->getDefinitions() AS $id => $definition)
        {
            if ( ! $definition->isSynthetic() && ! $definition->isAbstract() && $definition->isPublic())
            {
                // Transform %service_class% to concrete class, regarding parent
                // definitions.
                $classname = $this->findClassname($definition);
                
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
    private function collectAliases()
    {
        foreach ($this->container->getAliases() AS $name => $alias_definition)
        {
            if ($alias_definition->isPublic())
            {
                $target_definition = null;
                
                try 
                {
                    $target_definition = $this->container->findDefinition($name);
                }
                catch(\InvalidArgumentException $e)
                {
                    throw new DefinitionNotFoundException(sprintf('Service definition for alias "%s" could not be resolved.', $name), null, $e);
                }
                
                // SYNTHETIC DEFINITIONS HAS NO CLASSNAME
                if( ! $target_definition->isSynthetic())
                {
                    // transform %service_class% to concrete class, regarding parent
                    // definitions.
                    $classname = $this->findClassname($target_definition);

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
     * Finds the classname of a definition. If the given definition has no 
     * classname, it´s parent definitions will be searched.
     * Called by collectService() and collectAliases() methods.
     * 
     * @see collectServices()
     * @see collectAliases()
     * @param Definition $definition
     * @return string $class_name: The name of the next matching class (concrete 
     * 							    implementation) for the definition.
     */
    private function findClassname(Definition $definition)
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

                $parent = $this->container->hasDefinition($parent_id) ? $this->container->getDefinition($parent_id) : null;

                if (null == $parent)
                {
                    return null;
                }

                return $this->findClassname($parent);
            }
            elseif (null !== ($factory_class = $definition->getFactoryClass()))
            {
                return $this->findClassname($factory_class);
            }
        }
        
        // Returns the concrete classname instead of e.g. "%service_class%"
        return $this->getParameter($classname);
    }
    
    /**
     * Returns a single parameter which can be a concrete value (string) or
     * a placeholder like "%service.x.y.class_name%". If a placeholder is given,
     * it´s first matching value will be returned.
     * 
     * @see findClassname()
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
     * Returns the Id of matching service definition for the given class name.
     * 
     * @param string $typename: A classname including namespace in common PHP syntax
     * 
     * @return string $id: The service´s Id
     */
    public function resolveService($typename)
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
        
        // MIGHT BE AN INTERFACE
        else
        {
            $type = new \ReflectionClass($typename);
            
            if($type->isInterface())
            {
                foreach ($this->classMap + $this->aliasMap AS $key => $id)
                {
                    $class = new \ReflectionClass($key);

                    if($type->isInterface())
                    {
                        if($class->implementsInterface($type->getName()))
                        {
                            // service already resolved, ambiguous
                            if(null !== $service_id)
                            {
                                return false;
                            }
                            $service_id = $id;
                        }
                    }
                }
            }
        }
        return $service_id;
    }
    
    /**
     * Returns the DIC
     * 
     * @return Container
     */
    public function getContainer()
    {
        return $this->container;
    }
    
    /**
     * Resolves a single parameter which can be a concrete value (string) or a
     * placeholder like "%service.x.y.class_name%". If a placeholder is given,
     * it´s first matching value will be returned.
     * 
     * @see findClassname()
     * @param string $callout
     * 
     * @return mixed $parameter: The parameter or null if callout does not match to anyone.
     */
    private function resolveParameter($callout)
    {
        $this->parametersMap[$callout] = $retVal = $this->container->getParameterBag()->resolveValue($callout);

        return $retVal;
    }
}
