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

This bundle does not yet claim to be a masterpiece in application design, nor
it is (yet) configurable as promised in the small documentation below. It really
must be understood as a case-study for determining use-cases and solutions
to them.

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

    All services are defined by using a class annotation named "@Service".

2.  automatically wires (injects) dependencies to existing services as well 
    as annotation-defined services. Supported is
        
    a.  Constructor injection

    b.  Setter injection

    c.  Property injection
         
    All dependencies are defined by using a class annotation named @Inject

Note that you are able to use the traditional way of configuring services
by XML, YAML or plain PHP configuration and may later decide to inject
new dependencies into them. It is at least questionalable if this 
behaviour leads to clear application design, so you might want to avoid
this and concentrate about injecting dependencies into your controller classes
only, for example, by defining that as services by using the @Service 
annotation.

Additionally, dependencies can be wired by naming conventions. Naming 
conventions are supported for property injection only. Each property that ends 
up with "Service" is resolved by transforming the variable prefix into a valid
service identifier. For example,

    private $doctrineEntity_managerService;

will be transformed into "doctrine.entity_manager" and automatically resolved
by using the symfony´s DIC property injection feature.

Wiring by naming conventions is slightly magical, so you may want to disable
this feature (which is possible) or explicitly "type-hint" the definition-type
by using the "@Inject"-annotation:
        
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

Ambiguous services that share the same classname are excluded from this feature,
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

An alternative Syntax is provided to map dependencies to argument names:

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
@Inject hints! (Also note, that both the "plain" map-syntax and the strict,
common "array"-syntax in curly braces {} is supported. There is no strict
annotation property mapping.)

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
does not exist and @Strict sets how the service reference is validated by the
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

By default, all autowired dependencies are NOT optional (mandatory) and are
validated Strict.

Annotations for defining services
--------------------------------------------

This is an extremely useful feature in combination with the autowiring stuff
explained above. All you have to do is to define which classes are parsed
at the ContainerBuilder warmup (this happens once, then the DIC is persisted
as a concrete php class with simple getter and setter method in symfony´s cache
directory. The takes a really long time at this early state of development,
sure that there it space for optimization.)

You define services by annotating classes with the @Service annotation. As an
example i modified the Acme Welcome-controller of the symfony 2 standard 
edition´s Acme-Demo bundle a little bit:

    <?php

    namespace Acme\DemoBundle\Controller;

    use Symfony\Bundle\FrameworkBundle\Controller\Controller;
    use Ifschleife\Bundle\AutowiringBundle\Annotations\Service;

    /**
     * @Service (Id="my.welcome.controller")
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
        defaults: { _controller: my.welcome.controller:indexAction }

This route is not an "ordinary" controller/action definition but a 
"service-route" which means that it points to a controller that has been defined
as a DIC service.

Open the Welcome-Page in your browser (it´s the demo´s homepage). That´s it.


Alternative notations for @Service
----------------------------------
    
    @Service(Id="my.welcome.controller")

This is the most verbose and best understandable, thus recommended notation for
services. Also this notation allows you to provide additional configuration
options.

    @Service("my.welcome.controller")

(Note the missing "Id-Index", if your service has no options configured, the 
plain argument is assumed to be the service id.)

    @Service

By ommitting the service id, the injector automatically generates one for you,
consisting of the lowercased namespace-name of the class (separated by periods 
"." instead of backslashes "\" and a "tableized" class_name. For example,
the class 

    "Doctrine\ORM\EntityManager" 

will be transformed into the service name 
    
    "doctrine.orm.entity_manager"

, which follows the symfony DIC service naming convention.

Comments are very appreciated! 
           

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