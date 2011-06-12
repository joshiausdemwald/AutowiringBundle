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

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration
 *
 * @author joshi
 */
class Configuration implements ConfigurationInterface
{
    private $debug;

    /**
     * @var array $bundles: All registered bundles
     */
    private $bundles;
    
    /**
     * Constructor
     *
     * @param Boolean $debug Whether to use the debug mode
     */
    public function __construct($debug, array $bundles)
    {
        $this->bundles = $bundles;
        
        $this->debug = (Boolean) $debug;
    }

    /**
     * Generates the configuration tree builder.
     *
     * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        
        $bundles = $this->bundles; 
        
        $rootNode = $treeBuilder->root('autowiring');
        
        $rootNode
            ->children()
                ->booleanNode('enabled')->defaultTrue()->end()
                ->arrayNode('build_definitions')
                    ->addDefaultsIfNotSet()
                    ->canBeUnset()
                    ->treatNullLike(array('enabled'=>true))
                    ->treatFalseLike(array('enabled' => false))
                    ->fixXmlConfig('path')
                    ->children()
                        ->booleanNode('enabled')->defaultTrue()->end()
                        ->arrayNode('paths')
                            ->useAttributeAsKey('name')
                            ->beforeNormalization()
                                ->always()
                                ->then(function($paths) use ($bundles)
                                {
                                    $retVal = array();
                                    
                                    foreach($paths AS $path => $definition)
                                    {
                                        if(null !== $definition)
                                        {
                                            $path = array_key_exists('path', $definition) ? $definition['name'] : $path;
                                        }
                                        
                                        // IS BUNDLE REFERENCE?
                                        if(preg_match('#^@(.[^/]+)(.*?)$#', $path, $results))
                                        {
                                            $bundle_name = $results[1];
                                            $path_suffix = $results[2];

                                            if( ! array_key_exists($bundle_name, $bundles))
                                            {   
                                                throw new Loader\BundleNotFoundException(sprintf('Bundle "%s" could not be found or has not been registered in AppKernel.php. Please check your configuration.', $bundle_name));
                                            }

                                            $bundle_classname = $bundles[$bundle_name];

                                            try 
                                            {
                                                $class = new \ReflectionClass($bundle_classname);
                                                
                                                $fixed_pathname = dirname($class->getFilename()) . $path_suffix;
                                                
                                                $definition['pathname'] = $fixed_pathname;
                                                        
                                                $retVal[$fixed_pathname] = $definition;
                                            }
                                            catch(\Exception $e)
                                            {
                                                throw new Loader\BundleLoadErrorException(sprintf('Bundle "%s" could not be loaded.', $bundle_classname), null, $e);
                                            }
                                        }
                                        else
                                        {
                                            $definition['pathname'] = $path;
                                            
                                            $retVal[$path] = $definition;
                                        }
                                    }
                                    return $retVal;
                                })
                            ->end()
                            ->prototype('array')
                                ->validate()
                                    ->ifTrue(function($definition) {
                                        return ! file_exists($definition['pathname']) || !is_readable($definition['pathname']);
                                    })
                                    ->thenInvalid('Pathname at "%s" does not exist or is read-protected.')
                                    ->ifTrue(function($definition) {
                                        return is_dir($definition['pathname'] && ! array_key_exists('filename_pattern', $definition));
                                    })
                                    ->thenInvalid('You must specify a filename_pattern for directories at configuration "%s".')
                                ->end()
                                ->children()
                                    ->scalarNode('filename_pattern')->end()
                                    ->scalarNode('pathname')->isRequired()->end()
                                    ->booleanNode('recursive')->defaultTrue()->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('property_injection')
                    ->addDefaultsIfNotSet()
                    ->canBeUnset()
                    ->treatNullLike(array('enabled' => true))
                    ->treatFalseLike(array('enabled' => false))
                    ->children()
                        ->booleanNode('enabled')->defaultTrue()->end()
                        ->booleanNode('wire_by_name')->defaultTrue()->end()
                        ->scalarNode('name_suffix')->defaultValue('Service')->end()
                    ->end()
                ->end()
                ->arrayNode('setter_injection')
                    ->addDefaultsIfNotSet()  
                    ->canBeUnset()
                    ->treatNullLike(array('enabled' => true))
                    ->treatFalseLike(array('enabled' => false))
                    ->children()
                        ->booleanNode('enabled')->defaultTrue()->end()
                        ->booleanNode('wire_by_type')->defaultTrue()->end()
                    ->end()
                ->end()
                ->arrayNode('constructor_injection')
                    ->addDefaultsIfNotSet()
                    ->canBeUnset()
                    ->treatNullLike(array('enabled' => true))
                    ->treatFalseLike(array('enabled' => false))
                    ->children()
                        ->booleanNode('enabled')->defaultTrue()->end()
                        ->booleanNode('wire_by_type')->defaultTrue()->end()
                    ->end()
                ->end()
             ->end();
        return $treeBuilder;
    }
}
