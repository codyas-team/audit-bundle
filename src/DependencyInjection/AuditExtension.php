<?php

namespace Codyas\Audit\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class AuditExtension extends Extension
{

	/**
	 * {@inheritdoc}
	 */
	public function load( array $configs, ContainerBuilder $container )
	{
		$configuration = new Configuration();
		$config        = $this->processConfiguration( $configuration, $configs );
		$container->setParameter('codyas_audit_config', $config);
		$loader        = new YamlFileLoader(
			$container,
			new FileLocator( __DIR__ . '/../Resources/config' )
		);
		$loader->load( 'services.yaml' );

//		$this->addAnnotatedClassesToCompile( [
//			'Codyas\\Audit\\Controller\\CrudController',
//		] );
	}


}