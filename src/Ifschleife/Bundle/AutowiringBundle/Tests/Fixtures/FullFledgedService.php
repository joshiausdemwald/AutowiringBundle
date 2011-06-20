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

use Ifschleife\Bundle\AutowiringBundle\Annotations\Service;

/**
 * FullFledgedService
 *
 * @author joshi
 * @Service(Id="autowiring.full_fledged_service", Tags={"my.tag"}, Public=true, File="%test_path%")
 */
class FullFledgedService
{
    public static $TEST=false;
}

/**
 * FullFledgedService2
 *
 * @author joshi
 * @Service(Id="autowiring.full_fledged_service2", Tags={"my.tag2"}, Public=false, Scope="prototype")
 */
abstract class FullFledgedService2
{
    
}

/**
 * @Service(Id="autowiring.full_fledged_service3")
 */
class FullFledgedService3 extends FullFledgedService2
{
    
}

/**
 * @Service(Id="autowiring.full_fledged_service4", FactoryMethod="getInstance")
 */
class FullFledgedService4 
{
    public static function getInstance()
    {
        return new self();
    }
    
    public static function getInstance2()
    {
        return new FullFledgedService5();
    }
}

/**
 * @Service(Id="autowiring.full_fledged_service5", FactoryMethod={"@autowiring.full_fledged_service4", "getInstance"})
 */
class FullFledgedService5
{
    
}

/**
 * @Service(Id="autowiring.full_fledged_service6", FactoryMethod={"Ifschleife\Bundle\AutowiringBundle\Tests\Fixtures\FullFledgedService4", "getInstance"})
 */
class FullFledgedService6
{
    
}

/**
 * @Service(Id="autowiring.full_fledged_service7", Configurator="function(){}")
 */
class FullFledgedService7
{
    
}

/**
 * @Service(Id="autowiring.full_fledged_service8", Configurator={"Ifschleife\Bundle\AutowiringBundle\Tests\Fixtures\FullFledgedService8", "configure"})
 */
class FullFledgedService8
{
    public static function configure()
    {
        
    }
}