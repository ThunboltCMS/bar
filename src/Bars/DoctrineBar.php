<?php

declare(strict_types=1);

namespace Thunbolt\Bar\Bars;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\EntityManager;
use Nette\Http\IRequest;

class DoctrineBar extends Bar {

	/** @var EntityManager|EntityManagerInterface */
	private $em;

	public function __construct(IRequest $request, EntityManagerInterface $em) {
		parent::__construct($request);
		$this->em = $em;

		$this->callFunc('doctrineDelete', function ($val) {
			$this->delete($val);
			$this->redirectBack();
		});
		$this->callFunc('doctrineUpdate', function ($val) {
			$this->update($val);
			$this->redirectBack();
		});
		$this->callFunc('doctrineTruncate', function ($val) {
			$this->truncate($val);
			$this->redirectBack();
		});
		$this->callFunc('doctrineCreate', function ($val) {
			$this->create($val);
			$this->redirectBack();
		});
		$this->callFunc('doctrineUpdateAll', function () {
			$this->updateAll();
			$this->redirectBack();
		});
		$this->callFunc('doctrineDumpUpdate', function () {
			bdump($this->getUpdateAll(), null, [
				'truncate' => 15000,
			]);
		});
	}

	/************************* IBarPanel **************************/

	/**
	 * Renders HTML code for custom tab.
	 *
	 * @return string
	 */
	public function getTab(): string {
		ob_start();
		require __DIR__ . '/templates/doctrine.tab.phtml';
		return ob_get_clean();
	}

	/**
	 * Renders HTML code for custom panel.
	 *
	 * @return string
	 */
	public function getPanel(): string {
		ob_start();
		$tables = $this->getTables();
		require __DIR__ . '/templates/doctrine.panel.phtml';
		return ob_get_clean();
	}

	/////////////////////////////////////////////////////////////////

	protected function getUpdateAll(): ?string {
		$schemaTool = new SchemaTool($this->em);
		$classes = $this->em->getMetadataFactory()->getAllMetadata();

		if ($classes) {
			return implode(";\n", $schemaTool->getUpdateSchemaSql($classes));
		}

		return null;
	}

	protected function updateAll(): void {
		$schemaTool = new SchemaTool($this->em);
		$classes = $this->em->getMetadataFactory()->getAllMetadata();

		if ($classes) {
			$schemaTool->updateSchema($classes);
		}
	}

	/**
	 * @param array $classes
	 * @return array
	 */
	private function getTablesForCreate(array $classes): array {
		$currently = $this->em->getConnection()->getSchemaManager()->listTableNames();

		$toInstall = array();

		foreach ($classes as $row) {
			if (!in_array($row->table['name'], $currently) && $row->isMappedSuperclass !== TRUE) {
				$toInstall[] = $row;
			}
		}

		return $toInstall;
	}

	/**
	 * @param string $hash
	 */
	protected function create(string $hash): void {
		$tables = $this->getTables();
		if (!isset($tables[$hash]) || $tables[$hash]['isInstalled'] === TRUE) {
			return;
		}

		$entity = $this->em->getClassMetadata($tables[$hash]['name']);
		$schemaTool = new SchemaTool($this->em);
		$schemaTool->createSchema([$entity]);
	}

	/**
	 * @param string $hash
	 */
	protected function update(string $hash): void {
		$tables = $this->getTables();
		if (!isset($tables[$hash]) || $tables[$hash]['isInstalled'] === FALSE) {
			return;
		}

		$entity = $this->em->getClassMetadata($tables[$hash]['name']);
		$schemaTool = new SchemaTool($this->em);
		$schemaTool->updateSchema([$entity], true);
	}

	/**
	 * @param string $hash
	 */
	protected function delete(string $hash): void {
		$tables = $this->getTables();
		if (!isset($tables[$hash]) || $tables[$hash]['isInstalled'] === FALSE) {
			return;
		}

		$entity = $this->em->getClassMetadata($tables[$hash]['name']);
		$schemaTool = new SchemaTool($this->em);
		$schemaTool->dropSchema([$entity]);
	}

	private function getTables(): array {
		$classes = $this->em->getMetadataFactory()->getAllMetadata();
		$tables = $this->parseTables($classes);

		return $tables;
	}

	private function parseTables(array $classes): array {
		$currently = $this->em->getConnection()->getSchemaManager()->listTableNames();
		$return = [];
		foreach ($classes as $row) {
			if (!in_array($row->table['name'], $currently) && $row->isMappedSuperclass !== TRUE) {
				$return[md5($row->rootEntityName)] = [
					'name' => $row->rootEntityName,
					'table_name' => $row->table['name'],
					'isInstalled' => FALSE
				];
			} else if ($row->isMappedSuperclass !== TRUE) {
				$return[md5($row->rootEntityName)] = [
					'name' => $row->rootEntityName,
					'table_name' => $row->table['name'],
					'isInstalled' => TRUE
				];
			}
		}

		return $return;
	}

}
