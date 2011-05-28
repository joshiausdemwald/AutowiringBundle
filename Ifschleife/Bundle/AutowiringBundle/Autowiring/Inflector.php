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

/**
 * Inflector
 *
 * @author joshi
 */
class Inflector
{
	/**
     * Converts a camelized word into the format of a generic service id. 
	 * Converts 'SwiftmailerService' to 'swiftmailer', 
	 * 'doctrineEntity_managerService' to 'doctrine.entity_manager'.
     * Pattern stolen from doctrine inflector.
	 * (@copyright Konsta Vesterinen <kvesteri@cc.hut.fi> @copyright Jonathan H. Wage <jonwage@gmail.com>)
	 * 
     * @param  string $word  Word to transform
     * @return string $word  "serviceIdzed" word
     */
	public static function propertyName2ServiceId($propertyName)
	{
		$camelized = preg_replace('#Service$#', '', $propertyName);
					
        return strtolower(preg_replace('~(?<=\\w)([A-Z])~', '.$1', $camelized));
	}
}
