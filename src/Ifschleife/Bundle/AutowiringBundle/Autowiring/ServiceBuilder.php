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

namespace Ifschleife\Bundle\AutowiringBundle\Autowiring;

use Doctrine\Common\Annotations\Reader;

use Ifschleife\Bundle\AutowiringBundle\DependencyInjection\Loader\AnnotatedFileLoader;

/**
 * ServiceBuilder
 *
 * @author joshi
 */
class ServiceBuilder
{
    /**
     * A collection of filenames to parse
     * 
     * @var Iterator/array
     */
    protected $files;
    
    /**
     * @var AnnotatedFileLoader $loader
     */
    protected $loader;
    
    /**
     * Constructor.
     * 
     * @param Loader $loader
     */
    public function __construct(AnnotatedFileLoader $loader = null)
    {
        $this->loader = $loader;
    }
    
    /**
     * Sets all files that will be parsed for annotations
     * that define the containing classes as DIC Services.
     * 
     * @param Iterator/array $files
     * @return void
     */
    public function setFiles($files)
    {
        $this->files = $files;
    }

    /**
     * Builds all services read from class annotation metadata.
     * 
     * @return void
     */
    public function build()
    {
        foreach($this->files AS $file)
        {
            if($this->loader->supports((string)$file))
            {
                $this->loader->load($file);
            }
        }
    }
}
