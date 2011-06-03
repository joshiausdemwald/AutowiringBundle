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
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

use Doctrine\Common\Annotations\Reader;

use Ifschleife\Bundle\AutowiringBundle\Autowiring\ClassnameMapper;

/**
 * Injector
 * 
 * @author joshi
 */
abstract class Injector
{
    const ANNOTATION_INJECT     = 1;
    const ANNOTATION_OPTIONAL   = 2;
    const ANNOTATION_STRICT     = 4;
    const ANNOTATION_SERVICE    = 8;
    
    /**
     * @var ContainerBuilder
     */
    protected $container;
    
    /**
     * @var Reader $reader
     */
    protected $reader;
    
    /**
     * The read annotations map, indexes by one of the
     * ANNOTATION_* constants.
     * 
     * @var array $annotations
     */
    protected $annotations;
    
    /**
     *
     * @var ClassnameMapper
     */
    protected $classNameMapper;
    
    
    /**
     * Constructor.
     * 
     * @param \Reflector $reflector
     */
    public function __construct(ContainerBuilder $container, Reader $reader)
    {
        $this->container = $container;
        
        $this->reader = $reader;
    }
    
    protected abstract function process(Definition $definition, \Reflector $reflector);
    
    protected abstract function readAnnotations(\Reflector $reflector);
    
    /**
     * Starts the injection process for the given \Reflector
     */
    public function inject(Definition $definition, \Reflector $reflector)
    {
        $this->annotations = $this->createAnnotationsMap((array)$this->readAnnotations($reflector));
        
        $this->process($definition, $reflector);
    }
    
    /**
     * Sets the class name mapper.
     * 
     * @param ClassNameMapper $mapper 
     */
    public function setClassNameMapper(ClassNameMapper $mapper)
    {
        $this->classNameMapper = $mapper;
    }
    
    /**
     * Returns the classNameMapper. If no mapper was assigned, a new instance
     * will be created. WARNING: High CPU Usage!!
     * 
     * @return ClassNameMapper $classNameMapper
     */
    public function getClassNameMapper()
    {
        if(null === $this->classNameMapper)
        {
            $this->classNameMapper = new ClassnameMapper($this->container);
        }
        
        return $this->classNameMapper;
    }
    
    /**
     * Reads the annotations out of a reflector and returns them as a index
     * map indexed by the ANNOTATION_* constants.
     * 
     * @param \Reflector $reflector
     * @return type 
     */
    public function getAnnotations()
    {
        return $this->annotations;
    }
    
    /**
     * Returns true if the annotation with the given key exists, otherwise
     * false.
     * 
     * @param string $key: One of the ANNOTATION_* constants.
     * @return boolean $hasAnnotation: True if the annotation exists, otherwise false.
     */
    public function hasAnnotation($key)
    {
        return array_key_exists($key, $this->getAnnotations());
    }
    
    /**
     * Returns a single annotation, adressed by one of the
     * ANNOTATION_* constants.
     * 
     * @return \Doctrine\Common\Annotations\Annotation $annotation
     */
    public function getAnnotation($key)
    {
        return $this->annotations[$key];
    }
    
    /**
     * Creates an indexed map out of a flat list of annotations indexed by
     * the ANNOTATION_* constants.
     * 
     * @param array $annotations
     * @return array $annotationsMap
     */
    protected function createAnnotationsMap(array $annotations)
    {
        $retVal = array();
            
        foreach($annotations AS $annotation)
        {
            switch(get_class($annotation))
            {
                case 'Ifschleife\Bundle\AutowiringBundle\Annotations\Inject':
                    $retVal[self::ANNOTATION_INJECT]    = $annotation;
                    continue(2);
                case 'Ifschleife\Bundle\AutowiringBundle\Annotations\Optional':
                    $retVal[self::ANNOTATION_OPTIONAL]  = $annotation;
                    continue(2);
                case 'Ifschleife\Bundle\AutowiringBundle\Annotations\Strict':
                    $retVal[self::ANNOTATION_STRICT]    = $annotation;
                    continue(2);
                case 'Ifschleife\Bundle\AutowiringBundle\Annotations\Service':
                    $retVal[self::ANNOTATION_SERVICE]   = $annotation;
                    continue(2);
            }
        }

        return $retVal;
    }
    
    /**
     * Adds a new parameter to the DIC, identified by
     * a unique id.
     * 
     * @param type $value 
     * @return Id: A unique parameter id
     */
    protected function addParameter($value)
    {
        static $count = 1;
        
        static $id;
        
        if(null === $id)
        {
            $id = md5_file(__FILE__);
        }

        $service_id = $id . '_' . $count;
        
        $this->container->setParameter($service_id, $value);
        
        $count ++;
        
        return $service_id;
    }
    
    /**
     * Creates a new Reference for a service container definition.
     * 
     * @param string $service_id
     * @param boolean $is_optional
     * @param boolean $is_strict 
     */
    public function createReference($service_id, $is_optional = false, $is_strict = true)
    {
        return new Reference(
             $service_id,
             $is_optional ?
                Container::NULL_ON_INVALID_REFERENCE : 
                Container::EXCEPTION_ON_INVALID_REFERENCE,
             $is_strict
        );
    }
}
