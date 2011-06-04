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

namespace Ifschleife\Bundle\AutowiringBundle\DependencyInjection;

use Ifschleife\Bundle\AutowiringBundle\Autowiring\ServiceBuilder;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Finder\Finder;

/**
 * IfschleifeAutowiringExtension.
 *
 * @author joshi
 */
class AutowiringExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('autowiring.xml');
        
        $processor = new Processor();
        
        $configuration = new Configuration($container->getParameter('kernel.debug'));
        
        $config = $processor->processConfiguration($configuration, $configs);
        
        var_dump($config);
        
        $container->setParameter('autowiring.build_definitions', $config['build_definitions']);
        
        $container->setParameter('autowiring.property_injection', $config['property_injection']['enabled']);
        $container->setParameter('autowiring.property_injection.wire_by_name', $config['property_injection']['wire_by_name']['enabled']);
        $container->setParameter('autowiring.property_injection.name_suffix', $config['property_injection']['wire_by_name']['name_suffix']);
        
        $container->setParameter('autowiring.setter_injection', $config['setter_injection']['enabled']);
        $container->setParameter('autowiring.setter_injection.wire_by_type', $config['setter_injection']['wire_by_type']);
        
        $container->setParameter('autowiring.constructor_injection', $config['constructor_injection']['enabled']);
        $container->setParameter('autowiring.constructor_injection.wire_by_type', $config['constructor_injection']['wire_by_type']);
        
        $this->loadServices($container);
    }

    /**
     * Loads the @Service defined services.
     * @todo configure this stuff.
     */
    public function loadServices(ContainerBuilder $container, array $paths = null)
    {
        if($container->getParameter('autowiring.build_definitions'))
        {
            $serviceBuilder = new ServiceBuilder($container);
            $finder = new Finder();

            $serviceBuilder->setFiles($finder
                ->in($container->getParameter('kernel.root_dir') . '/../src/')
                ->name('#.*?Controller\.php#i')
            ->getIterator());

            $serviceBuilder->build();
        }
    }

    /**
     * Returns the base path for the XSD files.
     *
     * @return string The XSD base path
     */
    public function getXsdValidationBasePath()
    {
        return __DIR__ . '/../Resources/config/schema';
    }
    
    /**
     * Returns the configuration extension namespace.
     * 
     * @return string
     */
    public function getNamespace()
    {
        return 'http://ifschleife.de/schema/dic/autowiring';
    }
}
