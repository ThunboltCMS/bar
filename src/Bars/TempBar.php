<?php

declare(strict_types=1);

namespace Thunbolt\Bar\Bars;

use Nette\Application\Application;
use Nette\Http\IRequest;
use Nette\Utils\Finder;
use Thunbolt\Bar\BarException;
use Tracy\IBarPanel;

class TempBar extends Bar implements IBarPanel {

	/** @var string */
	private $tempDir;

	public function __construct(string $tempDir, IRequest $request, Application $application = NULL) {
		parent::__construct($request, $application);
		$this->tempDir = $tempDir;
		if (!class_exists(Finder::class)) {
			throw new BarException('Temp panel needs ' . Finder::class);
		}

		$this->callFunc('cache', function (string $val) {
			foreach (Finder::find('*')->in($this->tempDir . '/cache/' . $val) as $file) {
				unlink((string) $file);
			}

			$this->redirectBack();
		});
		$this->callFunc('cacheAll', function (string $val) {
			if ($val === 'yes') {
				foreach (Finder::findFiles('*')->in($this->tempDir . '/cache')->limitDepth(1) as $file) {
					unlink((string) $file);
				}

				$this->redirectBack();
			}
		});
		$this->callFunc('cacheFile', function (string $val) {
			if (is_string($val) && strpos($val, '/') === FALSE) {
				@unlink($this->tempDir . '/cache/' . $val); // @ - may not exists
				$this->redirectBack();
			}
		});
	}

	/**
	 * Renders HTML code for custom tab.
	 *
	 * @return string
	 */
	public function getTab(): string {
		ob_start();
		require __DIR__ . '/templates/temp.tab.phtml';
		return ob_get_clean();
	}

	/**
	 * Renders HTML code for custom panel.
	 *
	 * @return string
	 */
	public function getPanel(): string {
		ob_start();
		$cache = Finder::findDirectories('*')->in($this->tempDir . '/cache');
		$files = Finder::findFiles('*')->in($this->tempDir . '/cache');
		require __DIR__ . '/templates/temp.panel.phtml';
		return ob_get_clean();
	}

}
