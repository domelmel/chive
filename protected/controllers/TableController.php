<?php

class TableController extends CController
{
	const PAGE_SIZE=20;

	/**
	 * @var string specifies the default action to be 'list'.
	 */
	public $defaultAction='list';

	/**
	 * @var CActiveRecord the currently loaded data model instance.
	 */
	private $_table;
	private $_db;

	public $tableName;
	public $schemaName;

	/**
	 * @var Default layout for this controller
	 */
	public $layout = 'schema';

	public function __construct($id, $module=null) {

		$request = Yii::app()->getRequest();

		$this->tableName = $request->getParam('table');
		$this->schemaName = $request->getParam('schema');

		// @todo (rponudic) work with parameters!
		$this->_db = new CDbConnection('mysql:host='.Yii::app()->user->host.';dbname=' . $this->schemaName, Yii::app()->user->name, Yii::app()->user->password);
		$this->_db->charset='utf8';
		$this->_db->active = true;

		if(Yii::app()->request->isAjaxRequest) {
			$this->layout = "table";
		}

		parent::__construct($id, $module);

	}

	/**
	 * @return array action filters
	 */
	public function filters()
	{
		return array(
			'accessControl', // perform access control for CRUD operations
		);
	}

	/**
	 * Specifies the access control rules.
	 * This method is used by the 'accessControl' filter.
	 * @return array access control rules
	 */
	public function accessRules()
	{
		return array(
			array('allow',
					'expression' => !Yii::app()->user->isGuest,
			),
			array('deny',  // deny all users
					'users'=>array('*'),
			),
		);
	}

	/**
	 * Shows the table structure
	 */
	public function actionStructure()
	{

		$table = $this->loadTable();

		$indices = array();
		foreach($table->indices AS $index) {
			$indices[$index->INDEX_NAME][] = $index;
		}

		$this->render('structure',array(
			'table' => $table,
			'indices'=>$indices,
		));
	}

	/**
	 * Browse the rows of a table
	 */
	public function actionBrowse($_query = false)
	{

		$db = $this->_db;
		$error = false;

		$pages = new CPagination;
		$pages->setPageSize(self::PAGE_SIZE);

		$sort = new Sort($db);
		$sort->multiSort = false;



		if($_query instanceof CDbCriteria)
		{

			$criteria = $_query;
			$criteria->limit = self::PAGE_SIZE;

			$cmd = $db->getCommandBuilder()->createFindCommand($this->tableName, $criteria);

			$sql = $cmd->getText();

		}
		else
		{

			if(!$_query)
				$_query = 'SELECT * FROM ' . $db->quoteTableName($this->tableName);

			$oSql = new Sql($_query);
			$oSql->applyCalculateFoundRows();

			if(!$oSql->hasLimit)
			{
				$offset = (isset($_GET['page']) ? (int)$_GET['page'] : 1) * self::PAGE_SIZE - self::PAGE_SIZE;
				$oSql->applyLimit(self::PAGE_SIZE, $offset, true);
			}

			$oSql->applySort($sort->getOrder(), true);

			$cmd = $db->createCommand($oSql->getQuery());
			$cmd->prepare();

			$sql = $oSql->getOriginalQuery();

		}



		try
		{
			// Fetch data
			$data = $cmd->queryAll();

			if(!count($data))
				// @todo (rponudic) add redirect
				die("redirect");

			$total = $db->createCommand('SELECT FOUND_ROWS()')->queryScalar();
			$pages->setItemCount($total);

			// Fetch column headers
			$columns = array_keys($data[0]);

		}
		catch (Exception $ex)
		{
			$error = $ex->getMessage();
		}

		$this->render('browse',array(
			'data' => $data,
			'columns' => $columns,
			'query' => $sql,
			'pages' => $pages,
			'sort' => $sort,
			'error' => $error,
		));

	}

	/*
	 * Execute Sql
	 */
	public function actionSql() {

		$query = Yii::app()->getRequest()->getParam('query');

		if(!$query)
		{
			$this->render('browse',array(
				'data' => array(),
				'query' => self::getDefaultQuery(),
			));
		}
		else
			self::actionBrowse($query);

	}

	public function actionSearch() {

		$operators = array(
			'LIKE',
			'NOT LIKE',
			'=',
			'!=',
			'REGEXP',
			'NOT REGEXP',
			'IS NULL',
			'IS NOT NULL',
		);

		Row::$db = $this->_db;
		$row = new Row;

		$db = $this->_db;
		$commandBuilder = $this->_db->getCommandBuilder();

		if(isset($_POST['Row']))
		{

			$criteria = new CDbCriteria;
			$criteria->select = 'SQL_CALC_FOUND_ROWS *';

			$i = 0;
			foreach($_POST['Row'] AS $column=>$value) {

				if($value)
				{
					$operator = $operators[$_POST['operator'][$column]];
					$criteria->condition .= ($i>0 ? ' AND ' : '') . $db->quoteColumnName($column) . ' ' . $operator . ' :' . $column;
					$criteria->params[$column] = $value;

					$i++;
				}

			}

			self::actionBrowse($criteria);

		}
		else
		{
			$this->render('search', array(
				'row' => $row,
				'operators'=>$operators,
			));
		}

	}

	/**
	 * Insert a new row
	 * If creation is successful, the browser will be redirected to the 'browse' page.
	 */
	public function actionInsert()
	{

		Row::$db = $this->_db;
		$row = new Row;

		if(isset($_POST['Row']))
		{
			$row->attributes=$_POST['Row'];
			$row->isNewRecord = true;

			if(isset($_POST['submitRow']) && $row->save())
				Yii::app()->end('redirect:' . $this->schemaName . '#tables/' . $this->tableName . '/browse');

		}

		/*
		$table = $this->loadTable();

		if(isset($_POST['sent'])) {

			$builder = $this->_db->getCommandBuilder();

			$data = array();
			foreach($table->columns AS $column) {
				$data[$column->COLUMN_NAME] = $_POST[$column->COLUMN_NAME];
			}

			$cmd = $builder->createInsertCommand($this->tableName, $data);

			try
			{
				$cmd->prepare();
				$cmd->execute();
				Yii::app()->end('redirect:' . $this->schemaName . '#tables/' . $this->tableName . '/browse');
			}
			catch(CDbException $ex)
			{
				$errorInfo = $cmd->getPdoStatement()->errorInfo();
				//$this->addError('SCHEMA_NAME', Yii::t('message', 'sqlErrorOccured', array('{errno}' => $errorInfo[1], '{errmsg}' => $errorInfo[2])));
				return false;
			}

		}
		*/

		$functions = array(
			'',
			'ASCII',
			'CHAR',
			'MD5',
			'SHA1',
			'ENCRYPT',
			'RAND',
			'LAST_INSERT_ID',
			'UNIX_TIMESTAMP',
			'COUNT',
			'AVG',
			'SUM',
			'SOUNDEX',
			'LCASE',
			'UCASE',
			'NOW',
			'PASSWORD',
			'OLD_PASSWORD',
			'COMPRESS',
			'UNCOMPRESS',
			'CURDATE',
			'CURTIME',
			'UTC_DATE',
			'UTC_TIME',
			'UTC_TIMESTAMP',
			'FROM_DAYS',
			'FROM_UNIXTIME',
			'PERIOD_ADD',
			'PERIOD_DIFF',
			'TO_DAYS',
			'USER',
			'WEEKDAY',
			'CONCAT',
			'HEX',
			'UNHEX',
		);

		$this->render('insert',array(
			'row'=>$row,
			//'table'=>$table,
			'functions'=>$functions,
		));



	}

	/*
	 * Truncates the table
	 */
	public function actionTruncate()
	{

		try
		{
			$table = Table::model()->findByPk(array(
				'TABLE_SCHEMA' => $this->schemaName,
				'TABLE_NAME' => $this->tableName
			));
			$table->truncate();
		}
		catch(Exception $ex) {}

		Yii::app()->end('redirect(url); ');

	}

	/*
	 * Truncates the table
	 */
	public function actionDrop()
	{

		try
		{
			$table = Table::model()->findByPk(array(
				'TABLE_SCHEMA' => $this->schemaName,
				'TABLE_NAME' => $this->tableName
			));
			$table->drop();
		}
		catch(Exception $ex) {}

		Yii::app()->end();

	}

	/**
	 * Updates a particular user.
	 * If update is successful, the browser will be redirected to the 'show' page.
	 */
	public function actionUpdate()
	{
	}

	/**
	 * Deletes a particular user.
	 * If deletion is successful, the browser will be redirected to the 'list' page.
	 */
	public function actionDelete()
	{
	}

	/**
	 * Lists all users.
	 */
	public function actionList()
	{
		$criteria=new CDbCriteria;

		$pages=new CPagination(Schema::model()->count($criteria));
		$pages->pageSize=self::PAGE_SIZE;
		$pages->applyLimit($criteria);

		$criteria->group = 'SCHEMA_NAME';
		$criteria->select = 'COUNT(*) AS tableCount';

		$schemaList = Schema::model()->with(array(
			"table" => array('select'=>'COUNT(*) AS tableCount')
		))->together()->findAll($criteria);

		$this->render('list',array(
			'schemaList'=>$schemaList,
			'pages'=>$pages,
		));
	}

	/**
	 * Manages all users.
	 */
	public function actionAdmin()
	{
		$this->processAdminCommand();

		$criteria=new CDbCriteria;

		$pages=new CPagination(User::model()->count($criteria));
		$pages->pageSize=self::PAGE_SIZE;
		$pages->applyLimit($criteria);

		$sort=new CSort('User');
		$sort->applyOrder($criteria);

		$userList=User::model()->findAll($criteria);

		$this->render('admin',array(
			'userList'=>$userList,
			'pages'=>$pages,
			'sort'=>$sort,
		));
	}

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer the primary key value. Defaults to null, meaning using the 'id' GET variable
	 */
	public function loadTable($id=null)
	{
		if($this->_table===null)
		{
			if($id!==null || ($this->tableName && $this->schemaName))
			{
				$criteria = new CDbCriteria;
				$criteria->condition = 'TABLE_SCHEMA = :schema AND TABLE_NAME = :table';
				$criteria->params = array(
					'schema'=>$this->schemaName,
					'table'=>$this->tableName,
				);

				$table = Table::model()->find($criteria);
				$table->columns = Column::model()->findAll($criteria);
				$table->indices = Index::model()->findAll($criteria);

				$this->_table = $table;
			}

			if($this->_table===null)
				throw new CHttpException(500,'The requested table does not exist.');
		}
		return $this->_table;
	}

	/**
	 * Executes any command triggered on the admin page.
	 */
	protected function processAdminCommand()
	{
		if(isset($_POST['command'], $_POST['id']) && $_POST['command']==='delete')
		{
			$this->loadUser($_POST['id'])->delete();
			// reload the current page to avoid duplicated delete actions
			$this->refresh();
		}
	}

	private function getDefaultQuery()
	{
		return 'SELECT * FROM ' . $this->_db->quoteTableName($this->tableName) . "\n\t"
				. 'WHERE 1';
	}
}