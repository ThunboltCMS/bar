<?php

declare(strict_types=1);

namespace Thunbolt\Bar\DI;

use Doctrine\ORM\EntityManagerInterface;
use Nette\DI\CompilerExtension;
use Doctrine\ORM\EntityManager;
use Nette\PhpGenerator\ClassType;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Thunbolt\Bar\BarException;
use Thunbolt\Bar\Bars\DoctrineBar;
use Thunbolt\Bar\Bars\LogBar;
use Thunbolt\Bar\Bars\TempBar;
use Tracy\Bar;
use Tracy\Debugger;

class BarExtension extends CompilerExtension {

	/** @var string */
	private $tracyBarService;

	public function getConfigSchema(): Schema
	{
		$builder = $this->getContainerBuilder();

		return Expect::structure([
			'temp' => Expect::structure([
				'enable' => Expect::bool(true),
				'tempDir' => Expect::string($builder->parameters['tempDir']),
			]),
			'doctrine' => Expect::structure([
				'enable' => Expect::bool(class_exists(EntityManagerInterface::class)),
				'saveMode' => Expect::bool(false),
			]),
			'log' => Expect::structure([
				'enable' => Expect::bool(Debugger::$logDirectory && is_dir(Debugger::$logDirectory)),
				'logDir' => Expect::string(Debugger::$logDirectory),
			]),
		]);
	}

	/**
	 * Processes configuration data. Intended to be overridden by descendant.
	 */
	public function loadConfiguration(): void {
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig();

		$this->tracyBarService = $this->getContainerBuilder()
			->getByType(Bar::class);
		if (!$this->tracyBarService) {
			throw new BarException('Tracy bar not found in container.');
		}

		if ($config->temp->enable) {
			if (!$config->temp->tempDir) {
				throw new BarException('Temp dir has not been set.');
			}
			$builder->addDefinition($this->prefix('temp'))
				->setFactory(TempBar::class, [$config->temp->tempDir]);
		}
		if ($config->doctrine->enable) {
			$builder->addDefinition($this->prefix('doctrine'))
				->setFactory(DoctrineBar::class, ['saveMode' => $config->doctrine->saveMode]);
		}
		if ($config->log->enable && $config->log->logDir) {
			$builder->addDefinition($this->prefix('log'))
				->setFactory(LogBar::class, [$config->log->logDir]);
		}
	}

	/**
	 * Adjusts DI container compiled to PHP class. Intended to be overridden by descendant.
	 */
	public function afterCompile(ClassType $class): void {
		$builder = $this->getContainerBuilder();
		$init = $class->getMethods()['initialize'];

		if ($builder->parameters['consoleMode']) {
			return;
		}

		if ($builder->hasDefinition($this->prefix('temp'))) {
			$init->addBody('$this->getService(?)->addPanel($this->getService(?));', [
				$this->tracyBarService, $this->prefix('temp')
			]);
		}
		if ($builder->hasDefinition($this->prefix('doctrine'))) {
			$init->addBody('$this->getService(?)->addPanel($this->getService(?));', [
				$this->tracyBarService, $this->prefix('doctrine')
			]);
		}
		if ($builder->hasDefinition($this->prefix('log'))) {
			$init->addBody('$this->getService(?)->addPanel($this->getService(?));', [
				$this->tracyBarService, $this->prefix('log')
			]);
		}
	}

}
