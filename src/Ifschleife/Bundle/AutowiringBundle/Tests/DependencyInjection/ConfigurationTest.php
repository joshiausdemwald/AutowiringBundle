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

namespace Ifschleife\Bundle\AutowiringBundle\Tests\DependencyInjection;

use Ifschleife\Bundle\AutowiringBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;

/**
 * Configuration
 *
 * @author joshi
 */
class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    function testConfiguration()
    {
        $configuration = new Configuration(true, array(
            'FrameworkBundle' => 'Symfony\Bundle\FrameworkBundle\FrameworkBundle',
            'AutowiringBundle' => 'Ifschleife\Bundle\AutowiringBundle\AutowiringBundle',
            'IfschleifeWebsiteBundle' => 'Ifschleife\Bundle\WebsiteBundle\IfschleifeWebsiteBundle'
        ));
        
        
        $processor = new Processor();
        
        $config = $processor->processConfiguration($configuration, array());
        
        $this->assertTrue($config['enabled']);
        $this->assertTrue($config['build_definitions']['enabled']);
        $this->assertEmpty($config['build_definitions']['paths']);
        $this->assertTrue($config['property_injection']['enabled']);
        $this->assertTrue($config['property_injection']['wire_by_name']);
        $this->assertEquals('Service', $config['property_injection']['service_name_suffix']);
        $this->assertEquals('Parameter', $config['property_injection']['parameter_name_suffix']);
        $this->assertTrue($config['constructor_injection']['enabled']);
        $this->assertTrue($config['constructor_injection']['wire_by_type']);
        $this->assertTrue($config['setter_injection']['enabled']);
        $this->assertTrue($config['setter_injection']['wire_by_type']);
        
        $configs = array('autowiring' => array(
            'enabled' => false,
            'build_definitions' => false,
            'property_injection' => false,
            'setter_injection' => false
        ));
        
        $config = $processor->processConfiguration($configuration, $configs);
        
        $this->assertFalse($config['enabled']);
        $this->assertFalse($config['build_definitions']['enabled']);
        $this->assertFalse($config['property_injection']['enabled']);
        $this->assertFalse($config['setter_injection']['enabled']);
        
        $configs = array('autowiring' => array(
            'build_definitions' => array(
                'path' => array('name' => 'test/path/test')
            )
        ));
        
        $config = $processor->processConfiguration($configuration, $configs);
        
        $this->assertEquals('test/path/test', $config['build_definitions']['paths']['test/path/test']['pathname']);
        
        $configs = array('autowiring' => array(
            'build_definitions' => array(
                'paths' => array('test/path/test' => array())
            )
        ));
        
        $config = $processor->processConfiguration($configuration, $configs);
        
        $this->assertEquals('test/path/test', $config['build_definitions']['paths']['test/path/test']['pathname']);
        
        $configs = array('autowiring' => array(
            'property_injection' => array(
                'enabled' => true,
                'service_name_suffix' => 'Test',
                'parameter_name_suffix' => 'Test2',
            ),
            'constructor_injection' => array(
                'enabled' => false,
                'wire_by_type' => false
            ),'setter_injection' => array(
                'enabled' => false,
                'wire_by_type' => false
            )
        ));
        
        $config = $processor->processConfiguration($configuration, $configs);
        
        $this->assertEquals('Test', $config['property_injection']['service_name_suffix']);
        $this->assertEquals('Test2', $config['property_injection']['parameter_name_suffix']);
        $this->assertFalse($config['constructor_injection']['enabled']);
        $this->assertFalse($config['constructor_injection']['wire_by_type']);
        $this->assertFalse($config['setter_injection']['enabled']);
        $this->assertFalse($config['setter_injection']['wire_by_type']);
    }
}