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

namespace Ifschleife\Bundle\AutowiringBundle\Tests\Autowiring;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Ifschleife\Bundle\AutowiringBundle\Autowiring\ServiceBuilder;
use Ifschleife\Bundle\AutowiringBundle\DependencyInjection\Loader\AnnotatedFileLoader;

/**
 * Tests for ServiceBuilder
 *
 * @author joshi
 */
class ServiceBuilderTest extends \PHPUnit_Framework_TestCase
{
    function testConstruct()
    {
        $containerBuilder = new ContainerBuilder;
        
        $serviceBuilder = new ServiceBuilder($containerBuilder);        
        
        $this->assertAttributeInstanceOf('Ifschleife\Bundle\AutowiringBundle\DependencyInjection\Loader\AnnotatedFileLoader', 'loader', $serviceBuilder);
        
        return $serviceBuilder;
    }
    
    /**
     * @dataProvider files
     * @depends testConstruct
     */
    function testSetFiles(array $files, ServiceBuilder $serviceBuilder)
    {
        $serviceBuilder->setFiles($files);
        
        $this->assertAttributeContains('/home/joshi/www/vhosts/sf2test.int/src/src/Ifschleife/Bundle/AutowiringBundle/Tests/Autowiring/../Fixtures/CreateServiceTestclass1.php', 'files', $serviceBuilder);
        
        return $serviceBuilder;
    }
    
    /**
     * @dataProvider files
     */
    function testBuilder(array $files)
    {
        $containerBuilder = new ContainerBuilder;
        
        $serviceBuilder = new ServiceBuilder($containerBuilder);
        
        $serviceBuilder->setFiles($files);
        
        $serviceBuilder->build();
        
        $this->assertTrue($containerBuilder->hasDefinition('ifschleife.bundle.autowiring_bundle.tests.fixtures.create_service_testclass1'));
        $this->assertTrue($containerBuilder->hasDefinition('hans.wurst'));
    }
    
    function files()
    {
        return array(
            array(
                array(__DIR__ . '/../Fixtures/CreateServiceMalformed', __DIR__ . '/../Fixtures/Nofile', __DIR__ . '/../Fixtures/CreateServiceTestclass1.php',__DIR__ . '/../Fixtures/CreateServiceTestclass2.php'),
            )
        );
    }
}
