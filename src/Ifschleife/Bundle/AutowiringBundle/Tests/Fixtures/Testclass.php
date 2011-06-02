<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Ifschleife\Bundle\AutowiringBundle\Tests\Fixtures;

use Ifschleife\Bundle\AutowiringBundle\Annotations\Inject;
use Ifschleife\Bundle\AutowiringBundle\Annotations\Optional;
use Ifschleife\Bundle\AutowiringBundle\Annotations\Strict;
use Ifschleife\Bundle\AutowiringBundle\Annotations\Service;

/**
 * Description of Testclass
 *
 * @Service(Id="ifschleife.autowiring.testclass")
 * @author joshi
 */
class Testclass extends ParentTestclass
{
    private $testsvc;
    private $ifschleifeAutowiringTestserviceService;

    /**
     *
     * @param Testservice $testservice 
     * @Optional
     * @Inject
     */
    public function __construct(Testservice $testservice)
    {
        $this->testsvc = $testservice;
    }
}
