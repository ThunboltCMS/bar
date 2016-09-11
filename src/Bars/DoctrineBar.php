<?php

namespace Thunbolt\Bar\Bars;

use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\EntityManager;
use Nette\Http\Request;

class DoctrineBar extends Bar {

	/** @var EntityManager */
	private $em;

	public function __construct(Request $request, EntityManager $em) {
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
		$this->callFunc('doctrineCreateAll', function ($val) {
			$this->create($val);
			$this->redirectBack();
		});
	}

	/************************* IBarPanel **************************/

	/**
	 * Renders HTML code for custom tab.
	 *
	 * @return string
	 */
	public function getTab() {
		ob_start();
		require __DIR__ . '/templates/doctrine.tab.phtml';
		return ob_get_clean();
	}

	/**
	 * Renders HTML code for custom panel.
	 *
	 * @return string
	 */
	public function getPanel() {
		ob_start();
		$tables = $this->getTables();
		require __DIR__ . '/templates/doctrine.panel.phtml';
		return ob_get_clean();
	}

	/////////////////////////////////////////////////////////////////

	protected function createAll() {
		$schemaTool = new SchemaTool($this->em);
		$classes = $this->em->getMetadataFactory()->getAllMetadata();
		$classes = $this->getTablesForCreate($classes);

		if ($classes) {
			$schemaTool->createSchema($classes);
		}
	}

	/**
	 * @param array $classes
	 * @return array
	 */
	private function getTablesForCreate($classes) {
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
	protected function create($hash) {
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
	protected function truncate($hash) {
		$tables = $this->getTables();
		if (!isset($tables[$hash]) || $tables[$hash]['isInstalled'] === FALSE) {
			return;
		}

		$cmd = $this->em->getClassMetadata($tables[$hash]['name']);
		$connection = $this->em->getConnection();
		$dbPlatform = $connection->getDatabasePlatform();
		$connection->beginTransaction();

		try {
			//$connection->query('SET FOREIGN_KEY_CHECKS=0');
			$q = $dbPlatform->getTruncateTableSql($cmd->getTableName());
			$connection->executeUpdate($q);
			//$connection->query('SET FOREIGN_KEY_CHECKS=1');
			$connection->commit();
		} catch (\Exception $e) {
			$connection->rollback();
		}
	}

	/**
	 * @param string $hash
	 */
	protected function update($hash) {
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
	protected function delete($hash) {
		$tables = $this->getTables();
		if (!isset($tables[$hash]) || $tables[$hash]['isInstalled'] === FALSE) {
			return;
		}

		$entity = $this->em->getClassMetadata($tables[$hash]['name']);
		$schemaTool = new SchemaTool($this->em);
		$schemaTool->dropSchema([$entity]);
	}

	private function getTables() {
		$classes = $this->em->getMetadataFactory()->getAllMetadata();
		$tables = $this->parseTables($classes);

		return $tables;
	}

	private function parseTables($classes) {
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