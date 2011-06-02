<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Ifschleife\Bundle\AutowiringBundle\Tests\Fixtures;

use Ifschleife\Bundle\AutowiringBundle\Annotations\Service;

/**
 * Description of Testservice
 *
 * @Service("ifschleife.autowiring.testservice")
 * @author joshi
 */
class Testservice
{
    public function hello()
    {
        return 'hello';
    }
}
