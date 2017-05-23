<?php

declare(strict_types=1);

namespace Thunbolt\Bar\Bars;

use Nette\Application\Application;
use Nette\Http\IRequest;
use Nette\Utils\Finder;
use Nette\Utils\Strings;

class LogBar extends Bar {

	/** @var string */
	private $logDir;

	public function __construct(string $logDir, IRequest $request, Application $application = NULL) {
		parent::__construct($request, $application);
		$this->logDir = $logDir;

		$this->callFunc('logShow', function (string $val) {
			$file = $this->logDir . '/' . $val;
			if (!file_exists($file)) {
				echo '<h3>Log file not found.</h3>';
				$this->terminate();
				return;
			}

			if (Strings::endsWith($val, '.log')) {
				echo str_replace("\n", '<br>', file_get_contents($this->logDir . '/' . $val));
			} else {
				readfile($this->logDir . '/' . $val);
			}
			$this->terminate();
		});
		$this->callFunc('logDelete', function (string $val) {
			$file = $this->logDir . '/' . $val;
			if (file_exists($file)) {
				unlink($file);
			}

			$this->redirectBack();
		});
	}

	public function getTab(): string {
		ob_start();
		$count = count(glob($this->logDir . '/*.html'));
		require __DIR__ . '/templates/log.tab.phtml';
		return ob_get_clean();
	}

	public function getPanel(): string {
		ob_start();
		$logs = Finder::findFiles(['*.html', '*.log'])->in($this->logDir);
		require __DIR__ . '/templates/log.panel.phtml';
		return ob_get_clean();
	}

}
