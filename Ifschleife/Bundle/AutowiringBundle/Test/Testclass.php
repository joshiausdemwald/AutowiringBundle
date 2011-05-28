<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Ifschleife\Bundle\AutowiringBundle\Test;

use Ifschleife\Bundle\AutowiringBundle\Annotations\Inject;

/**
 * Description of Testclass
 *
 * @author joshi
 */
class Testclass extends ParentTestclass
{
    private $testsvc;
    private $ifschleifeAutowiringTestserviceService;

    /**
     *
     * @param Testservice $testservice 
     * @Inject
     */
    public function __construct(Testservice $testservice)
    {
        $this->testsvc = $testservice;
    }
}
