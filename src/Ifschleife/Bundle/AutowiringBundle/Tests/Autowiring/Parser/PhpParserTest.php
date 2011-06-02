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
namespace Ifschleife\Bundle\AutowiringBundle\Tests\Autowiring\Parser;

use Ifschleife\Bundle\AutowiringBundle\Tests\Fixtures;
use Ifschleife\Bundle\AutowiringBundle\Autowiring\Parser\PhpParser;

class PhpParserTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        
    }
    
    public function testParseFile()
    {
        $parser = new PhpParser();
        $result = $parser->parseFile(__DIR__ . '/../../Fixtures/PhpParserTestclass0.php');
        $this->assertInternalType('array', $result);
        $this->assertEquals(0, count($result));
        
        $result = $parser->parseFile(__DIR__ . '/../../Fixtures/PhpParserTestclass1.php');
        $this->assertInternalType('array', $result);
        $this->assertEquals(1, count($result));
        $this->assertArrayHasKey('Ifschleife\Bundle\AutowiringBundle\Tests\Fixtures\PhpParserTestclass1', $result);
        
        $result = $parser->parseFile(__DIR__ . '/../../Fixtures/PhpParserTestclass2.php');
        $this->assertInternalType('array', $result);
        $this->assertEquals(2, count($result));
        $this->assertArrayHasKey('Ifschleife\Bundle\AutowiringBundle\Tests\Fixtures\PhpParserTestclass2', $result);
        $this->assertArrayHasKey('Ifschleife\Bundle\AutowiringBundle\Tests\Fixtures\PhpParserTestclass2a', $result);
        
        
        $result = $parser->parseFile(__DIR__ . '/../../Fixtures/PhpParserTestclass3.php');
        $this->assertInternalType('array', $result);
        $this->assertEquals(3, count($result));
        $this->assertArrayHasKey('Ifschleife\Bundle\AutowiringBundle\Tests\Fixtures\PhpParserTestclass3', $result);
        $this->assertArrayHasKey('Ifschleife\Bundle\AutowiringBundle\Tests\Fixtures\PhpParserTestclass3a', $result);
        $this->assertArrayHasKey('Ifschleife\Bundle\AutowiringBundle\Tests\Fixtures\PhpParserTestclass3b', $result);
        
        $result = $parser->parseFile(__DIR__ . '/../../Fixtures/PhpParserTestclass4.php');
        $this->assertInternalType('array', $result);
        $this->assertEquals(2, count($result));
        $this->assertArrayHasKey('Ifschleife\Bundle\AutowiringBundle\Tests\Fixtures\PhpParserTestclass4', $result);
        $this->assertArrayHasKey('Ifschleife\Bundle\AutowiringBundle\Tests\Fixtures\PhpParserTestclass4a', $result);
    }
    
    public function tearDown()
    {
        
    }
}
