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
namespace Ifschleife\Bundle\AutowiringBundle\Autowiring\Parser
{
    use Ifschleife\Bundle\AutowiringBundle\Autowiring\AutowiringException;
    
    /**
     * Parses a file for namespaces/use/class declarations.
     *
     * @author Fabien Potencier <fabien@symfony.com>
     * @author Johannes Heinen <johannes.heinen@gmail.com>
     */
    final class PhpParser
    {
        const SERVICE_ANNOTATION_TOKEN = 'Service';

        /**
         * Parses all classes found in the given file. Must have a namespace 
         * configured. Returns a list of \ReflectionClass instances.
         * 
         * @todo provide a reliable shortcut for single-class files
         * @param string $filename 
         * @return array $classes: An array of all found classes within the file as 
         *                         instances of \ReflectionClass
         */
        public function parseFile($filename)
        {
            $classes = array();

            //if(preg_match('#/\*(?:.*?)@(?:.*?)' . self::SERVICE_ANNOTATION_TOKEN . '\s*(?:\(.+?\))?(?:.*?)\*/\s*class\b#s', file_get_contents($filename), $matches))
          //  {       
                $src = php_strip_whitespace($filename);

                /**
                 * Idea & Regex-Pattern derived from \Doctrine\Common\Annotations\PhpParser (@author Fabien Potencier <fabien@symfony.com>
                 */
                if(preg_match_all('#(?:(?:\bnamespace\s+(.+?)\s*(?:;|\\{).*?)?\bclass\s+(.+?)\b)+?#s', $src, $matches))
                {
                    $classes = array();

                    $namespace = null;
                    
                    for($i = 0; $i < count($matches[0]); $i++) 
                    {
                        // REMEMBER PREVIOUS NAMESPACE
                        if($matches[1][$i])
                        {
                            $namespace = $matches[1][$i];
                        }
                        
                        $classname = $namespace . '\\' . $matches[2][$i];

                        if( ! class_exists($classname))
                        {
                            require_once $filename;
                        }

                        try 
                        {
                            $classes[$classname] = new \ReflectionClass($classname);
                        }
                        catch(\ReflectionException $e)
                        {
                            throw new PhpParserException(sprintf('PHP parser exception: Class "%s" in file "%s" could not be autoloaded.', $classname, realpath($filename)), null, $e);
                        }   
                    }
                }
         //   }

            return $classes;
        }
    }
    
    /**
     * Parser Exception class
     */
    class PhpParserException extends AutowiringException
    {

    }
}
