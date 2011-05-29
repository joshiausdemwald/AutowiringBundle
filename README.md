README
======

What is Autowiring Bundle?
-----------------

*Autowiring Bundle* is a *Symfony 2* bundle that enables autowiring for services
as well as any container aware class (more precisely: any class).

*NOTE*: This bundle is in a very early stadium. There is 0% code coverage, no 
guarantee that it`ll work at any circumstances and one advice:

*DON´T EVER USE THIS BUNDLE IN PRODUCTION ENVIRONMENTS UNTIL THIS WARNING HAS 
DISAPPEARED!*

The bundle is an _experiment_ (that´s why it´s called "experiment") to see what
may be achieved by DI-Autowiring, to determine which features are "good", and 
which are "bad" in the sense of "too complex", "too magically" or simply 
redundant.

I would love to get comments on my work! *Don't hesitate beeing upfront*!


Autowiring bundle consists of a container compiler pass that

1.  automatically enables preselected classes for beeing defined as DI-
    Services by using a simple annotation syntax. This is a full featured 
    alternative to the common way defining services by XML, Yaml or plain 
    PHP files.

    All services are defined by using a class annotation named @Service

2.  automatically wires (injects) dependencies to existing services as well 
    as annotation-defined services. Supported is
        
    a.  Constructor injection

    b.  Setter injection

    c.  Property injection
         
    All dependencies are defined by using a class annotation named @Inject

Additionally, dependencies can be wired by naming conventions. Naming 
conventions are supported for property injection only. Each property that ends 
up with "Service" are resolved by transforming the variable prefix into a valid
service identifier. For example,

    private $doctrineEntity_managerService;

Will be transformed into "doctrine.entity_manager" and automatically resolved
by using the symfony´s DIC property injection feature.

Wiring by naming conventions is slightly magical, so you may want to disable
this feature (which is possible) or explicetly "type-hint" the definition-type
by using the @Inject-annotation:
        
    /**
     * @Inject("doctrine.entity_manager")
     */
    private $entityManager;

Dependencies may even be wired by introspection (the only aspect of DI that
supports introspection-wiring is setter-injection). If a instance method has a 
qualified signature with type-hints in it, the bundle tries to resolve the 
expected types by analyzing all services in the DIC. For example, a method like

    /**
     * @Inject
     */
    public function setEntityManager(\Doctrine\ORM\EntityManager $em)
    {
        $this->em = $em;
    }

will automatically gain a addMethodCall() in the DIC that´ll be constructed by
the ContainerBuilder.

Ambigous services that share the same classname are excluded from this feature,
so you hopefully won't suffer from magic tricks that are hard to debug.

In the case a service is ambiguous or you want to explicetly define the
dependency, use the @Inject annotation:

    /**
     * @Inject("doctrine.entity_manager")
     */
    public function setEntityManager(\Doctrine\ORM\EntityManager $em)
    {
        $this->em = $em;
    }

This also works for multiple arguments too, of course:

    /**
     * @Inject({"doctrine.entity_manager", "my.mighty.mailer"})
     */
    public function setEntityManager(
                     \Doctrine\ORM\EntityManager $em, 
                     \Mailer $mailer, 
    ) {
        $this->em = $em;

        $this->mailer = $mailer
    }

An alternative Syntax is provided to map dependencies on argument names:

    /**
     * @Inject(mailer="my.mighty.mailer", em="doctrine.entity_manager")
     */
    public function setEntityManager(
                     \Doctrine\ORM\EntityManager $em, 
                     \Mailer $mailer, 
    ) {
        $this->em = $em;

        $this->mailer = $mailer
    }

The order of the arguments provided is not important when you use named 
@Inject hints!

You may even leave some arguments blank if they can be resolved by the type-
lookup resolver:

    /**
     * @Inject(mailer="my.mighty.mailer")
     */
    public function setEntityManager(
                     \Doctrine\ORM\EntityManager $em, 
                     \Mailer $mailer, 
    ) {
        $this->em = $em;

        $this->mailer = $mailer
    }

The bundle should be smart enough to resolve the not explicitly defined
dependencies by analyzing the method signature.

Instead of services you are also allowed to map DIC-Parameters or even plain 
values.

Annotations for fine-tuning the dependencies
--------------------------------------------

You may use the @Optional and @Strict dependencies to control how the DIC deals
with them. Internally, @Optional controls exception handling when the service
does not exists and @Strict sets how the service reference is validated by the
DIC.

Example:
        
    /**
     * @Strict(false)
     * @Optional
     * @Inject("my.mighty.mailer")
     */
    public function setEntityManager(
                     \Doctrine\ORM\EntityManager $em, 
                     \Mailer $mailer, 
    ) {
        $this->em = $em;

        $this->mailer = $mailer
    }

or:
    
    /**
     * @Strict
     * @Optional(false)
     * @Inject("my.mighty.mailer")
     */
    public function setEntityManager(
                     \Doctrine\ORM\EntityManager $em, 
                     \Mailer $mailer, 
    ) {
        $this->em = $em;

        $this->mailer = $mailer
    }

Per default, all autowired dependencies are NOT optional (mandatory) and are
validated Strict.

Annotations for defining services
--------------------------------------------

This is an extremely useful feature in combination with the autowiring stuff
explained above. All you have to do is to define which classes are parsed
at the ContainerBuilder warmup (this happens once, than the DIC is persisted
as a concrete php class with simple getter and setter method in symfony´s cache
directory).

You define services by annotating classes with the @Service annotation. As an
example i modified the Acme Welcome-controller of the symfony 2 standard 
edition´s Acme-Demo bundle a little bit:

    <?php

    namespace Acme\DemoBundle\Controller;

    use Symfony\Bundle\FrameworkBundle\Controller\Controller;
    use Ifschleife\Bundle\AutowiringBundle\Annotations\Service;

    /**
     * @Service (Id="acme.demo.controller.welcome_controller")
     */
    class WelcomeController
    {
        /**
         * @var Symfony\Bundle\TwigBundle\TwigEngine
         */
        private $templatingService;

        public function indexAction()
        {
            return $this->templatingService->renderResponse('AcmeDemoBundle:Welcome:index.html.twig');
        }
    }

Notice the @Service annotation and the $templatingService instance variable
(see the Service-Suffix that triggers that auto-wiring functionality by naming
conventions).

After that i slightly modified the routing_dev.yml (note that per default, the
Acme-Bundle is only enabled in DEV-mode):

    _welcome:
        pattern:  /
        defaults: { _controller: acme.demo.controller.welcome_controller:indexAction }

This route is a not a "ordinary" controller/action definition but a 
"service-route" which means that it points to a controller that has been defined
as a DIC service.

Open the Welcome-Page in your browser (it´s the demo´s homepage). Thats it.

Comments very appreciated! 
           

Requirements
------------

Symfony2 is only supported on PHP 5.3.2 and up.


Installation
------------

The best way to install Symfony2 is to download the Symfony Standard Edition
available at [http://symfony.com/download][1].

Then install this bundle by cloning it into your /src folder. Register the 
"Ifschleife" namespace (sorry for the silly name) in your autoload.php, after
that enable the Bundle in your AppKernel:

        new Ifschleife\Bundle\AutowiringBundle\AutowiringBundle()

Thats it.