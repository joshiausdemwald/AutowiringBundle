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

namespace Ifschleife\Bundle\AutowiringBundle\Tests\DependencyInjection\Loader;


use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Ifschleife\Bundle\AutowiringBundle\Annotation\AnnotationReaderDecorator;
use Ifschleife\Bundle\AutowiringBundle\DependencyInjection\Loader\AnnotatedFileLoader;
use Ifschleife\Bundle\AutowiringBundle\Autowiring\Parser\PhpParser;

/**
 * AnnotatedFileLoaderTest
 *
 * @author joshi
 */
class AnnotatedFileLoaderTest extends \PHPUnit_Framework_Testcase
{
    /**
     * @dataProvider Files
     */
    function testFiles($file)
    {
        $container = new ContainerBuilder;
        
        $parser = new PhpParser();
        
        $reader = new AnnotationReaderDecorator();
        
        $locator = new FileLocator();
        
        $containerInjector = new \Ifschleife\Bundle\AutowiringBundle\Autowiring\Injector\ContainerInjector($container, $reader);
        
        $loader = new AnnotatedFileLoader($container, $containerInjector, $locator, $parser);
        
        $this->assertTrue($loader->supports($file));
    }
    
    /**
     * @dataProvider malformedFiles
     */
    function testMalformedFiles($file)
    {
        $container = new ContainerBuilder;
        
        $parser = new PhpParser();
        
        $reader = new AnnotationReaderDecorator();
        
        $locator = new FileLocator();
        
        $containerInjector = new \Ifschleife\Bundle\AutowiringBundle\Autowiring\Injector\ContainerInjector($container, $reader);
        
        $loader = new AnnotatedFileLoader($container, $containerInjector, $locator, $parser);
        
        $this->assertFalse($loader->supports($file));
    }
    
    /**
     * Tests optional arguments like "scope", "tags", "File", "Public"
     * @dataProvider filesWithOptionalArguments
     */
    function testOptionalArguments($file)
    {
        $container = new ContainerBuilder();
        
        $container->setParameter('test_path', __DIR__ . '/../../Fixtures/init.php');
        
        $parser = new PhpParser();
        
        $reader = new AnnotationReaderDecorator();
        
        $locator = new FileLocator();
        
        $containerInjector = new \Ifschleife\Bundle\AutowiringBundle\Autowiring\Injector\ContainerInjector($container, $reader);
        
        $loader = new AnnotatedFileLoader($container, $containerInjector, $locator, $parser);
        
        $this->assertTrue($loader->supports($file));
        
        $loader->load($file);
        
        $this->assertInstanceOf('Ifschleife\Bundle\AutowiringBundle\Tests\Fixtures\FullFledgedService', $service = $container->get('autowiring.full_fledged_service'));
        
        // SET BY PRE-REQUIRED FILE
        $this->assertTrue(\Ifschleife\Bundle\AutowiringBundle\Tests\Fixtures\FullFledgedService::$TEST);
        
        $this->assertTrue($container->getDefinition('autowiring.full_fledged_service')->isPublic());
        
        $this->assertContains('my.tag', $container->getDefinition('autowiring.full_fledged_service')->getTags());
        
        $this->assertFalse($container->getDefinition('autowiring.full_fledged_service2')->isPublic());
        
        $this->assertTrue($container->getDefinition('autowiring.full_fledged_service2')->isAbstract());
    }
    
    function files()
    {
        return array(
            array(__DIR__ . '/../../Fixtures/CreateServiceTestclass1.php')
        );
    }
    
    function malformedFiles()
    {
        return array(
            array(__DIR__ . '/../../Fixtures/NonExistent'),
            array(__DIR__ . '/../../Fixtures/CreateServiceMalformed1.xml')
        );
    }
    
    function filesWithOptionalArguments()
    {
        return array(
            array(__DIR__ . '/../../Fixtures/FullFledgedService.php')
        );
    }
}
