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

namespace Ifschleife\Bundle\AutowiringBundle\Annotation;

use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\DocParser;

/**
 * AnnotationReaderDecorator decorates core AnnotationReader
 *
 * @author joshi
 */
class AnnotationReaderDecorator implements Reader
{
    /**
     * @var Reader
     */
    protected $reader;
    
    /**
     * @var DocParser
     */
    protected $parser;
    
    public function __construct(Reader $reader = null, DocParser $parser = null)
    {
        $this->reader = $reader;
        
        $this->parser = $parser;
        
        if(null === $this->reader)
        {
            $this->reader = new AnnotationReader();
        }
        
        if(null === $this->parser)
        {
            $this->parser = new DocParser;
            $this->parser->setIgnoreNotImportedAnnotations(true);
        }
        
        $this->fixCoreReaderIssues();
    }
    
    /**
     * Fixes some issues that makes it impossible
     * to inherting from the core reader classes and
     * perform fine-granded configuration.
     * 
     * @return void
     */
    protected function fixCoreReaderIssues()
    {
        $parser = new \ReflectionProperty($this->reader, 'parser');
        $parser->setAccessible(true);
        $parser->setValue($this->reader, $this->parser);
    }
    
    /**
     * Gets the annotations applied to a class.
     *
     * @param string|ReflectionClass $class The name or ReflectionClass of the class from which
     * the class annotations should be read.
     * @return array An array of Annotations.
     */
    public function getClassAnnotations(\ReflectionClass $class)
    {
       return $this->reader->getClassAnnotations($class); 
    }
    
    /**
     * Gets a class annotation.
     *
     * @param ReflectionClass $class The ReflectionClass of the class from which
     *                               the class annotations should be read.
     * @param string $annotationName The name of the annotation.
     * @return The Annotation or NULL, if the requested annotation does not exist.
     */
    public function getClassAnnotation(\ReflectionClass $class, $annotationName)
    {
        return $this->reader->getClassAnnotation($class, $annotationName);
    }
    
    /**
     * Gets the annotations applied to a method.
     *
     * @param ReflectionMethod $property The name or ReflectionMethod of the method from which
     * the annotations should be read.
     * @return array An array of Annotations.
     */
    function getMethodAnnotations(\ReflectionMethod $method)
    {
        return $this->reader->getMethodAnnotations($method);
    }
    
    /**
     * Gets a method annotation.
     *
     * @param ReflectionMethod $method
     * @param string $annotationName The name of the annotation.
     * @return The Annotation or NULL, if the requested annotation does not exist.
     */
    function getMethodAnnotation(\ReflectionMethod $method, $annotationName)
    {
        return $this->reader->getMethodAnnotation($method, $annotationName);
    }
    
    /**
     * Gets the annotations applied to a property.
     *
     * @param string|ReflectionProperty $property The name or ReflectionProperty of the property
     * from which the annotations should be read.
     * @return array An array of Annotations.
     */
    function getPropertyAnnotations(\ReflectionProperty $property)
    {
        return $this->reader->getPropertyAnnotations($property);
    }
    
    /**
     * Gets a property annotation.
     *
     * @param ReflectionProperty $property
     * @param string $annotationName The name of the annotation.
     * @return The Annotation or NULL, if the requested annotation does not exist.
     */
    function getPropertyAnnotation(\ReflectionProperty $property, $annotationName)
    {
        return $this->reader->getPropertyAnnotation($property, $annotationName);
    }
}
