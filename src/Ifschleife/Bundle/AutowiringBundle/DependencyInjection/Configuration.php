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
     * Constructor
     *
     * @param Boolean $debug Whether to use the debug mode
     */
    public function __construct($debug)
    {
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
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('filename_pattern')->isRequired()->end()
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
                        ->arrayNode('wire_by_name')
                            ->addDefaultsIfNotSet()  
                            ->canBeUnset()
                            ->treatNullLike(array('enabled' => true))
                            ->treatFalseLike(array('enabled' => false))
                            ->children()
                                ->booleanNode('enabled')->defaultTrue()->end()
                                ->scalarNode('name_suffix')->defaultValue('Service')->end()
                            ->end()
                        ->end()
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
