<?php

namespace Thunbolt\Bar\DI;

use Nette;
use Nette\DI\CompilerExtension;
use Doctrine\ORM\EntityManager;
use Thunbolt\Bar\BarException;
use Thunbolt\Bar\Doctrine;
use Thunbolt\Bar\Temp;
use Tracy\Bar;

class BarExtension extends CompilerExtension {

	/** @var array */
	private $defaults = [
		'enable' => [
			'temp' => TRUE,
			'doctrine' => TRUE
		]
	];

	/** @var string */
	private $tracyBarService;

	/**
	 * Processes configuration data. Intended to be overridden by descendant.
	 */
	public function loadConfiguration() {
		$builder = $this->getContainerBuilder();
		$config = $this->validateConfig($this->defaults, $this->getConfig());

		$this->tracyBarService = $this->getContainerBuilder()->getByType(Bar::class);
		if (!$this->tracyBarService) {
			throw new BarException('Tracy bar not found in container.');
		}

		if ($config['enable']['temp']) {
			if (!isset($builder->parameters['tempDir'])) {
				throw new BarException('Temp dir has not been set.');
			}
			$builder->addDefinition($this->prefix('temp'))
				->setClass(Temp::class, [$builder->parameters['tempDir']]);
		}
		if ($config['enable']['doctrine'] && class_exists(EntityManager::class)) {
			$builder->addDefinition($this->prefix('doctrine'))
				->setClass(Doctrine::class);
		}
	}

	/**
	 * Adjusts DI container compiled to PHP class. Intended to be overridden by descendant.
	 *
	 * @param Nette\PhpGenerator\ClassType $class
	 */
	public function afterCompile(Nette\PhpGenerator\ClassType $class) {
		$builder = $this->getContainerBuilder();
		$init = $class->getMethods()['initialize'];

		if ($builder->hasDefinition($this->prefix('temp'))) {
			$init->addBody('if ($this->parameters["debugMode"]) $this->getService(?)->addPanel($this->getService(?));', [
					$this->tracyBarService, $this->prefix('temp')
				]);
		}
		if ($builder->hasDefinition($this->prefix('doctrine'))) {
			$init->addBody('if ($this->parameters["debugMode"]) $this->getService(?)->addPanel($this->getService(?));', [
					$this->tracyBarService, $this->prefix('doctrine')
				]);
		}
	}

}
