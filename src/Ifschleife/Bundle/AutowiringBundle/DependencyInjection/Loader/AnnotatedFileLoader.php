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
use Symfony\Component\Config\Resource\FileResource;

use Doctrine\Common\Annotations\Reader;

use Ifschleife\Bundle\AutowiringBundle\Autowiring\Parser\PhpParser;
use Ifschleife\Bundle\AutowiringBundle\Autowiring\Injector\ContainerInjector;

/**
 * AnnotationFileLoader
 *
 * @author joshi
 */
class AnnotatedFileLoader extends FileLoader
{
    /**
     * @var ContainerBuilder
     */
    protected $container;
    
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
     * @var ContainerInjector
     */
    protected $containerInjector;
    
    /**
     * Constructor.
     * 
     * @param ContainerBuilder $container 
     * @param ContainerInjector $containerInjector
     * @param FileLocator $fileLocator
     * @param PhpParser $phpParser
     */
    public function __construct(ContainerBuilder $container, ContainerInjector $containerInjector, FileLocator $locator, PhpParser $parser)
    {
        $this->container = $container;
        
        $this->containerInjector = $containerInjector;
        
        $this->phpParser = $parser;
        
        $this->locator = $locator;
    }
    
    /**
     * @return ContainerBuilder
     */
    public function getContainer()
    {
        return $this->container;
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
        $this->injectServices();
    }

    /**
     * Returns true if this class supports the given resource.
     *
     * @param mixed  $resource A resource
     * @param string $type     The resource type
     *
     * @return Boolean true if this class supports the given resource, false otherwise
     */
    public function supports($resource, $type = null)
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
     * Creates Services from the given \Reflection classes.
     * 
     * @return void
     */
    protected function injectServices()
    {
        foreach($this->classes AS $class)
        {
            $this->containerInjector->injectService($class);
        }
    }
}
