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
use Symfony\Component\Finder\Finder;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Annotations\AnnotationReader;
use Ifschleife\Bundle\AutowiringBundle\Autowiring\Parser\PhpParser;
use Symfony\Component\DependencyInjection\Definition;

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
    private $container;
    
    /**
     * @var boolean
     */
    private $isInitialized;
    
    /**
     * A collection of filenames to parse
     * 
     * @var Iterator/array
     */
    private $files;
    
    /**
     * @var Reader
     */
    private $reader;
   
    /**
     * @var phpParser
     */
    private $phpParser;
    
    /**
     * @var array<\ReflectionClass>
     */
    private $classes;
    
    /**
     * Constructor.
     * 
     * @param ContainerBuilder $container 
     */
    public function __construct(ContainerBuilder $container, Reader $reader = null)
    {
        $this->container = $container;
        
        $this->phpParser = new PhpParser();
        
        if(null === $reader)
        {
            $this->reader = new AnnotationReader();
        }
        
        $this->isInitialized = false;
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
     * Gets the default set of files to parse. Default files are
     * all files under ./Controller subdirectories of ./src dir.
     * 
     * If no files specified by setFile(), this default set will
     * be taken for processing.
     * 
     * Use setFiles() to override the default files and define an
     * individual file set to parse and define as DIC services.
     * 
     * @return void
     */
    public function getDefaultFileSet()
    {
        $finder = new Finder;
        
        return $finder
            ->in($this->container->getParameter('kernel.root_dir') . '/../src/')
            ->files()
            ->name('#.*?Controller\.php#i')
        ->getIterator();
    }
    
    /**
     * Initializes all instance variables.
     * 
     * @return void
     */
    public function initialize()
    {
        $this->classes = array();
        
        if(null === $this->files)
        {
            $this->files = $this->getDefaultFileSet();
        }
        
        $this->isInitialized = true;
    }
    
    /**
     * Builds all services read from class annotation metadata.
     * 
     * @return void
     */
    public function build()
    {
        if( ! $this->isInitialized)
        {
            $this->initialize();
        }
        
        $this->collectClasses();
        
        $this->createServices();
        
        $this->isInitialized = false;
    }
    
    /**
     * Collects the files and extracts the potential service classes.
     * 
     * @return void
     */
    private function collectClasses()
    {
        foreach($this->files AS $file)
        {
            $this->classes = array_merge($this->classes, $this->phpParser->parseFile($file));
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
        
        if(array_key_exists(ServiceResolver::ANNOTATION_SERVICE, $annotations))
        {
            $annotation = $annotations[ServiceResolver::ANNOTATION_SERVICE];
            
            $service_id = $annotation->getId();
            
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
        return ServiceResolver::getAnnotationsStatic($class, $this->reader);
    }
}
