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

namespace Ifschleife\Bundle\AutowiringBundle\Tests\Fixtures;

use Ifschleife\Bundle\AutowiringBundle\Annotations\Inject;
use Ifschleife\Bundle\AutowiringBundle\Annotations\Service;

/**
 * Description of ParentTestclass
 *
 * @author joshi
 * @Service
 */
class ParentTestclass
{
    private $testservice;

    /**
     * @Inject
     */
    public function setTestservice(Testservice $testservice)
    {
        $this->testservice = $testservice;
    }

    public function getTestservice()
    {
        return $this->testservice;
    }
    
    /**
     * @Inject(foo="foo", bar="bar")
     */
    public function setFoo($foo, $bar)
    {
        
    }
    
    /**
     * @Inject({"foo", "bar"})
     */
    public function setBar($foo, $bar)
    {
        
    }
    
    /**
     * @param \Doctrine\ORM\EntityManager $em
     * @param type $foo 
     * @Inject(foo="bar")
     */
    public function setFoobar1(\Ifschleife\Bundle\AutowiringBundle\Tests\Fixtures\Testservice $service, $foo)
    {
        
    }
    
    /**
     * @param \Doctrine\ORM\EntityManager $em
     * @param type $foo 
     * @Inject(foo="bar")
     */
    public function setFoobar2($foo, \Ifschleife\Bundle\AutowiringBundle\Tests\Fixtures\Testservice $service)
    {
        
    }
}
