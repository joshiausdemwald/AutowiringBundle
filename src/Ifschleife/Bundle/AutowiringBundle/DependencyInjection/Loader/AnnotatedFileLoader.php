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

namespace Ifschleife\Bundle\AutowiringBundle\DependencyInjection\Loader;

use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\Config\Resource\FileResource;

use Doctrine\Common\Annotations\Reader;

use Ifschleife\Bundle\AutowiringBundle\Autowiring\Parser\PhpParser;
use Ifschleife\Bundle\AutowiringBundle\Autowiring\ServiceResolver;
use Ifschleife\Bundle\AutowiringBundle\Annotation\ParameterMismatchException;

/**
 * AnnotationFileLoader
 *
 * @author joshi
 */
class AnnotatedFileLoader extends FileLoader
{
    /**
     *
     * @var ContainerBuilder
     */
    protected $container;
    
    /**
     * @var Reader
     */
    protected $reader;
   
    /**
     * @var phpParser
     */
    protected $phpParser;
    
    /**
     * @var array<\ReflectionClass>
     */
    protected $classes;
    
    /**
     * @var string $path: The absolute path to the php class file (given by the locator).
     */
    protected $classFilePath;
    
    /**
     * @var FileLocatorInterface
     */
    protected $locator;
    
    /**
     * Constructor.
     * 
     * @param ContainerBuilder $container 
     */
    public function __construct(ContainerBuilder $container, FileLocator $locator, PhpParser $parser, Reader $reader)
    {
        $this->container = $container;
        
        $this->phpParser = $parser;
        
        $this->reader = $reader;
        
        $this->locator = $locator;
    }
    
    /**
     * Loads an XML file.
     *
     * @param mixed  $resource The resource
     * @param string $type The resource type
     */
    public function load($resource, $type = null)
    {
        // FILE RESOURCE ENABLES THE CONTAINER TO CHECK IF IT IS UP-TO-DATE
        $this->container->addResource(new FileResource($this->classFilePath));
        
        // services
        $this->parseDefinitions();
    }

    /**
     * Returns true if this class supports the given resource.
     *
     * @param mixed  $resource A resource
     * @param string $type     The resource type
     *
     * @return Boolean true if this class supports the given resource, false otherwise
     */
    function supports($resource, $type = null)
    {   
        if(is_string($resource) && 'php' === pathinfo($resource, PATHINFO_EXTENSION))
        {
            $this->classFilePath = $this->locator->locate($resource);
            
            $this->classes = $this->phpParser->parseFile($this->classFilePath);
                    
            return count($this->classes) > 0;
        }
        
        return false;
    }
    
    /**
     * Searches the found classes within the given file for @Service tags
     * and creates DIC definitions from them.
     * 
     * @return void
     */
    function parseDefinitions()
    {
        foreach($this->classes AS $class)
        {
            $this->parseDefinition($class);
        }
    }
    
    /**
     * Creates a service for the given \ReflectionClass.
     * 
     * @param \ReflectionClass $class
     * @return string $service_id: The newly created or already existant service id or NULL if no definition could be created.
     */
    private function parseDefinition(\ReflectionClass $class)
    {
        $annotations = $this->getAnnotations($class);
        
        if(array_key_exists(ServiceResolver::ANNOTATION_SERVICE, $annotations))
        {
            $annotation = $annotations[ServiceResolver::ANNOTATION_SERVICE];
            
            $service_id = $annotation->getId();
            
            if(null === $service_id)
            {
                throw new ParameterMismatchException(sprintf('Expected Service-Id "Id", NULL given on service annotation @Service in class "%s". Try \'@Service(Id="your.service.id")\'.', $class->getName()));
            }
            
            if($this->container->has($service_id))
            {
                return $service_id;
            }
            
            $this->container->setDefinition($service_id, $this->createDefinition($class));
            
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
    public function createDefinition(\ReflectionClass $class)
    {
        if(false !== ($parentClass = $class->getParentClass()) && null !== ($parent_service_id = $this->parseDefinition($parentClass)))
        {
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
     * Returns the @Service and other di related annotation tags for a class.
     *
     * @param \ReflectionClass $class 
     * @return array
     */
    public function getAnnotations(\ReflectionClass $class)
    {
        return ServiceResolver::getAnnotationsStatic($class, $this->reader);
    }
}
