<?php

namespace Codyas\Audit\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{

	public function getConfigTreeBuilder()
	{
		$treeBuilder = new TreeBuilder( 'codyas_audit' );
		$treeBuilder->getRootNode()
            ->children()
	            ->arrayNode( 'doctrine' )
		            ->children()
		                ->scalarNode( 'manager' )->defaultValue('default')->end()
	                ->end()
	            ->end()
	            ->arrayNode( 'serialization' )
		            ->children()
		                ->scalarNode( 'group_name' )->defaultValue('audit')->end()
	                ->end()
	            ->end()
            ->end();

		return $treeBuilder;
	}

}