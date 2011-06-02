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

use \Ifschleife\Bundle\AutowiringBundle\Annotations\Annotation;

/**
 * AnnotationsMap
 *
 * @author joshi
 */
class AnnotationsMap
{
    /**
     * @var array
     */
    private $diHints;
    
    /**
     * @var \ReflectionProperty
     */
    private $properties;
    
    /**
     * @var array
     */
    private $propertyMap;
    
    /**
     * Constructor.
     * 
     * @param array $diHints
     * @param \ReflectionProperty $property 
     */
    public function __construct(array $diHints, array $properties)
    {
        $this->diHints = $diHints;
        
        $this->properties = $properties;
        
        $this->createMap();
    }
    
    /**
     * 
     */
    public function hasHint($index)
    {
        return array_key_exists($index, $this->propertyMap);
    }
    
    public function getHint($index)
    {
        return $this->propertyMap[$index];
    }
    
    public function getIsParameter($index = null)
    {
        return $this->detectResourceType($index) === 'parameter';
    }
    
    public function getIsReference($index)
    {
        return $this->detectResourceType($index) === 'reference';
    }
    
    public function getIsPlainValue($index)
    {
        return $this->detectResourceType($index) === 'plain_value';
    }
    
    /**
     * Detects the resource type off the given value.
     * "@"-prefixed: Service reference
     * "%"-pre-and-suffixed: Parameter reference
     * 
     * @param type $value 
     * @return string
     */
    protected function detectResourceType($index)
    {
        $value = $this->propertyMap[$index];
        
        if(0 === strpos($value, '@'))
        {
            return 'reference';
        }
        elseif(0 === strpos($value, '%') && '%' === substr($value, -1, 1))
        {
            return 'parameter';
        }
        return 'plain_value';
    }
    
    /**
     * @return array
     */
    public function getResourceNames()
    {
        return $this->propertyMap;
    }
    
    public function getResourceName($index)
    {
        switch($this->detectResourceType($index))
        {
            case 'reference':
                return substr($this->propertyMap[$index], 1);
            case 'parameter':
                return substr(substr($this->propertyMap[$index], 1), -1, 1);
        }
        
        return $this->propertyMap[$index];
    }
    
    private function createMap()
    {
        $this->propertyMap = array();
        
        foreach($this->properties AS $i => $property)
        {
            if(array_key_exists($i, $this->diHints))
            {
                $this->propertyMap[$i] = $this->diHints[$i];
            }
            elseif(array_key_exists($property->getName(), $this->diHints))
            {
                $this->propertyMap[$i] = $this->diHints[$property->getName()];
            }
        }
    }
}
