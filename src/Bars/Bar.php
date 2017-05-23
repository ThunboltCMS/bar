<?php

declare(strict_types=1);

namespace Thunbolt\Bar\Bars;

use Nette\Application\Application;
use Nette\Http\IRequest;
use Tracy\IBarPanel;

abstract class Bar implements IBarPanel {

	private const PREFIX = 'wch-';

	/** @var \Nette\Http\UrlScript */
	private $url;

	/** @var string */
	private $lastParam;

	/** @var Application */
	private $application;

	public function __construct(IRequest $request, Application $application = NULL) {
		$this->url = $request->getUrl();
		$this->application = $application;
	}

	protected function terminate(): void {
		if ($this->application) {
			$this->application->onStartup = [function () {
				exit(1);
			}];
		} else {
			exit(1);
		}
	}

	/**
	 * @param string $param
	 * @param string|callable $func
	 */
	protected function callFunc(string $param, $func): void {
		$this->lastParam = self::PREFIX . $param;
		if (($val = $this->url->getQueryParameter($this->lastParam)) !== NULL) {
			$func = is_callable($func) ? $func : [$this, $func];
			call_user_func($func, $val);
		}
	}

	protected function redirectBack(): void {
		header('Location: ' . $this->url->setQueryParameter($this->lastParam, NULL));
		exit(1);
	}

	/**
	 * @param array $parameters
	 * @return string
	 */
	public function link(array $parameters): string {
		$url = clone $this->url;
		foreach ($parameters as $name => $value) {
			$url->setQueryParameter($name, $value);
		}

		return (string) $url;
	}

	/**
	 * @param string $param
	 * @param mixed $val
	 * @return string
	 */
	public function fastLink(string $param, $val): string {
		$url = clone $this->url;
		$url->setQueryParameter(self::PREFIX . $param, $val);

		return (string) $url;
	}

}
