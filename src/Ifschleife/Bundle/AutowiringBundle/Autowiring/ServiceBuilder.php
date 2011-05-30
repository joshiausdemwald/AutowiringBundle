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

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Doctrine\Common\Annotations\Reader;

use Ifschleife\Bundle\AutowiringBundle\DependencyInjection\Loader\AnnotatedFileLoader;

/**
 * ServiceBuilder
 *
 * @author joshi
 */
class ServiceBuilder
{
    /**
     *
     * @var ContainerBuilder
     */
    protected $container;
    
    /**
     * A collection of filenames to parse
     * 
     * @var Iterator/array
     */
    protected $files;
    
    /**
     * @var AnnotatedFileLoader $loader
     */
    protected $loader;
    
    /**
     * Constructor.
     * 
     * @param ContainerBuilder $container 
     */
    public function __construct(ContainerBuilder $container, AnnotatedFileLoader $loader = null)
    {
        $this->container = $container;
        
        $this->loader = $loader;
        
        if(null === $loader)
        {
            $this->loader = new AnnotatedFileLoader(
                $container,
                new \Symfony\Component\Config\FileLocator(),
                new Parser\PhpParser,
                new \Ifschleife\Bundle\AutowiringBundle\Annotation\AnnotationReader()
            );
        }
    }
    
    /**
     * Sets all files that will be parsed for annotations
     * that define the containing classes as DIC Services.
     * 
     * @param Iterator/array $files
     * @return void
     */
    public function setFiles($files)
    {
        $this->files = $files;
    }

    /**
     * Builds all services read from class annotation metadata.
     * 
     * @return void
     */
    public function build()
    {
        foreach($this->files AS $file)
        {
            if($this->loader->supports((string)$file))
            {
                $this->loader->load($file);
            }
        }
    }
    
    /**
     * Creates a service for each class in the class list.
     * 
     * @return void
     */
    private function createServices()
    {
        foreach($this->classes AS $class)
        {
            /* @var $class \ReflectionClass */
            $definition = $this->createService($class);
        }
    }
    
    /**
     * Creates a service for the given \ReflectionClass.
     * 
     * @param \ReflectionClass $class
     * @return Definition $definition
     */
    private function createService(\ReflectionClass $class)
    {
        $annotations = $this->getAnnotations($class);
        
        if(array_key_exists(DependencyResolver::ANNOTATION_SERVICE, $annotations))
        {
            $annotation = $annotations[DependencyResolver::ANNOTATION_SERVICE];
            
            $service_id = $annotation->getId();
            
            // Regards definitions, aliases and concrete getters named getXYZ()
            if($this->container->has($service_id))
            {
                throw new DuplicateServiceIdException(sprintf('A service named "%s" already exists in the DIC.', $service_id));
            }

            // New Service, create definition and append to the DIC. Don't 
            // append any arguments, they will be determined by constructor
            // introspection in later calls.
            $definition = new Definition($class->getName(), array());
            
            $definition->setPublic(true);
            
            $this->container->setDefinition($service_id, $definition);
        }
    }
    
    /**
     * Returns the @Service and other di related annotation tags for a class.
     *
     * @param \ReflectionClass $class 
     * @return array
     */
    public function getAnnotations(\ReflectionClass $class)
    {
        return DependencyResolver::getAnnotationsStatic($class, $this->reader);
    }
}
