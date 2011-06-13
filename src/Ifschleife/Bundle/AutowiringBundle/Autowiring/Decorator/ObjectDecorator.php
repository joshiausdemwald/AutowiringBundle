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

namespace Ifschleife\Bundle\AutowiringBundle\Autowiring\Decorator\ObjectDecorator;

use \Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * ObjectDecorator
 *
 * @author joshi
 */
class ObjectDecorator
{
    /**
     * @var ContainerAware
     */
    protected $object;
    
    /**
     * @var Container
     */
    protected $container;
    
    /**
     * @param ContainerAware $object: A variable instance to decorate
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
    
    /**
     * Creates a new instance of the given $classname.
     * 
     * @param string $classname
     * @return ContainerAware $class
     */
    public function produce($classname)
    {
        
    }
    
    public function create($classname, array $arguments = array())
    {
        $class = new \ReflectionClass($classname);
        
        return $class->newInstance($arguments);
    }
    
    /**
     * Decorates a ContainerAware with services injected
     * by annotations.
     * 
     * @param ContainerAware $object 
     */
    public function decorate(ContainerAware $object)
    {
        $this->object->setContainer($this->container);
    }
}
