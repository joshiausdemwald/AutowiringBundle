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
        
        $container->setParameter('autowiring.config.enabled', $config['enabled']);
        
        $container->setParameter('autowiring.config.build_definitions', $config['build_definitions']['enabled']);
        $container->setParameter('autowiring.config.build_definitions.paths', $config['build_definitions']['paths']);
        
        $container->setParameter('autowiring.config.property_injection', $config['property_injection']['enabled']);
        $container->setParameter('autowiring.config.property_injection.wire_by_name', $config['property_injection']['wire_by_name']['enabled']);
        $container->setParameter('autowiring.config.property_injection.name_suffix', $config['property_injection']['wire_by_name']['name_suffix']);
        
        $container->setParameter('autowiring.config.setter_injection', $config['setter_injection']['enabled']);
        $container->setParameter('autowiring.config.setter_injection.wire_by_type', $config['setter_injection']['wire_by_type']);
        
        $container->setParameter('autowiring.config.constructor_injection', $config['constructor_injection']['enabled']);
        $container->setParameter('autowiring.config.constructor_injection.wire_by_type', $config['constructor_injection']['wire_by_type']);
        
        $this->loadServices($container);
    }

    /**
     * Loads the @Service defined services.
     * @todo configure this stuff.
     */
    public function loadServices(ContainerBuilder $container, array $paths = null)
    {
        if($container->getParameter('autowiring.config.enabled') && $container->getParameter('autowiring.config.build_definitions'))
        {
            $serviceBuilder = new ServiceBuilder($container);

            $bundles = $container->getParameter('kernel.bundles');
            
            $iterator = new \AppendIterator();
            
            foreach($container->getParameter('autowiring.config.build_definitions.paths') as $path => $parameters)
            {
                // IS BUNDLE REFERENCE?
                if(preg_match('#^@(.[^/]+)(.*?)$#', $path, $results))
                {
                    $bundle_name = $results[1];
                    $path_suffix = $results[2];
                    
                    if( !array_key_exists($bundle_name, $bundles))
                    {
                        throw new Loader\BundleNotFoundException(sprintf('Bundle "%s" could not be found or has not been registered in AppKernel.php. Please check your configuration.', $bundle_name));
                    }
                    
                    $bundle_classname = $bundles[$bundle_name];
                    
                    try 
                    {
                        $class = new \ReflectionClass($bundle_classname);
                        
                        $iterator->append($this->loadPath(dirname($class->getFilename() . $path_suffix), $parameters['recursive'], $parameters['filename_pattern']));
                    }
                    catch(\Exception $e)
                    {
                        throw new Loader\BundleLoadErrorException(sprintf('Bundle "%s" could not be loaded.', $bundle_classname), null, $e);
                    }
                }
                else
                {
                    $iterator->append($this->loadPath($path, $parameters['recursive'], $parameters['filename_pattern']));
                }
            }
            
            $serviceBuilder->setFiles($iterator);
            $serviceBuilder->build();
        }
    }
    
    /**
     * Loads a path to register as services.
     * 
     * @param string $path: An absolute path
     * @param boolean $recursive: Whether to search subdirectories
     * @param string $filename_pattern: A regular expression/glob filename pattern.
     * @return Iterator
     */
    private function loadPath($path, $recursive, $filename_pattern)
    {
        if(is_dir($path))
        {
            $finder = new Finder();
            $finder->files()->in($path)->name($filename_pattern);
            
            if( ! $recursive)
            {
              $finder->depth(0);
            }
            
            return $finder->getIterator();
        }
        elseif(file_exists($path))
        {
            return new \ArrayIterator(array($path));
        }
        
        throw new Loader\FileNotFoundException(sprintf('File "%s" could not be found or is read protected.', $path));
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
