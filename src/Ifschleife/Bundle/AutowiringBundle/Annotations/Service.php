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

namespace Ifschleife\Bundle\AutowiringBundle\Annotations;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Service Annotation (@Service)
 *
 * @author joshi
 */
class Service extends Annotation
{
    /**
     * @var string $id: The service´s id
     */
    public $Id;
    
    /**
     *
     * @var boolean $public: True if the service should be public, otherwise 
     *                      false.
     */
    public $Public;
    
    /**
     * @var string: A file to include before instanciating the service.
     */
    public $File;
    
    /**
     * @var array
     */
    public $Tags;
    
    /**
     * @var array || string $factoryMethod: A php callable or a simple string 
     *                                      that refers to the $this-Object.
     */
    public $FactoryMethod;
    
    /**
     * The scope of the definition
     * 
     * @link https://github.com/kriswallsmith/symfony-scoped-container
     * 
     * ContainerScope
     * The container scope will always return the same instance of a service. 
     * It will be created when first called, stored in the scope, and that same 
     * instance returned again for successive calls.
     *
     * PrototypeScope
     * The prototype scope will always create a new instance of whatever 
     * services in contains.
     * 
     * (NOT IMPLEMENTED):
     * NestingContainerScope
     * The nesting scope functions the same way as the container scope, but is 
     * aware of being entered and left. Upon being entered, the internal 
     * collection of stored services is reset so new instances will be create 
     * when called for. When left, the internal collection of services will 
     * revert to the previous collection, hence the term nested.
     * 
     * @var string
     */
    public $Scope;
    
    public function getId()
    {
        if(null !== $this->Id)
        {
            return $this->Id;
        }
        elseif(null !== $this->value && is_string($this->value))
        {
            return $this->value;
        }
        
        return null;
    }
    
    public function getPublic()
    {
        if(null === $this->Public)
        {
            return true;
        }
        return $this->Public;
    }
    
    /**
     * @return string $file: The file to include or null
     */
    public function getFile()
    {
        return $this->File;
    }
    
    /**
     * @return array $tags: The tags, default: an empty array()
     */
    public function getTags()
    {
        return (array)$this->Tags;
    }    
    
    public function getScope()
    {
        if(null === $this->Scope || 'container' === $this->Scope)
        {
            return ContainerInterface::SCOPE_CONTAINER;
        }
        
        return ContainerInterface::SCOPE_PROTOTYPE;
    }
    
    /**
     * Returns a factory method as a valid php callable
     * or a string that refers to a factory method on the
     * $this-object.
     * 
     * @return array || string $factoryMethod
     */
    public function getFactoryMethod()
    {
        return $this->FactoryMethod;
    }
}
