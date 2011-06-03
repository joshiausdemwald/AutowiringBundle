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
        
        $loader = new AnnotatedFileLoader($container, $locator, $parser, $reader);
        
        $this->assertTrue($loader->supports($file));
    }
    
    /**
     * @dataProvider malformedFiles
     */
    function testMalformedFiles($file)
    {
        $container = new ContainerBuilder;
        
        $parser = new PhpParser();
        
        $reader = new AnnotationReaderDecorator;
        
        $locator = new FileLocator();
        
        $loader = new AnnotatedFileLoader($container, $locator, $parser, $reader);
        
        $this->assertFalse($loader->supports($file));
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
}
