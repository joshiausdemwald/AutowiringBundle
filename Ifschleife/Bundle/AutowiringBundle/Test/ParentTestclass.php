<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Ifschleife\Bundle\AutowiringBundle\Test;

use Ifschleife\Bundle\AutowiringBundle\Annotations\Inject;

/**
 * Description of ParentTestclass
 *
 * @author joshi
 */
class ParentTestclass
{
    private $testservice;

    /**
     * @Inject
     */
    public function setTestservice(Testservice $testservice)
    {
        $this->testservice = $testservice;
    }

    public function getTestservice()
    {
        return $this->testservice;
    }
}
