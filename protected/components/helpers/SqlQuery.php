<?php

// Import Sql Parser and Compiler
Yii::import('application.extensions.sqlquery.*');
Yii::import('application.extensions.sqlquery.Sql_Compiler.*');
Yii::import('application.extensions.sqlquery.Sql_Dialect.*');
Yii::import('application.extensions.sqlquery.Sql_Parser.*');
Yii::import('application.extensions.sqlquery.Sql_Interface.*');

class SqlQuery {

	private $query;

	private $sqlParser;
	private $parsedQuery;
	private $parsedOriginalQuery;

	private $sqlCompiler;

	public $comments;

	public $originalQuery;

	private $resultSetTypes = array(
		'select',
		'show',
		'analyze',
		'repair',
		'check',
	);

	public function __construct($_query) {

		$this->query = $this->originalQuery = $_query;
		self::stripComments();

		$this->sqlCompiler = new Sql_Compiler();

		try
		{
			$this->sqlParser = new Sql_Parser($this->query);
			$this->parsedQuery = $this->parsedOriginalQuery = $this->sqlParser->parse();

			var_dump($this->parsedQuery);

			#predie($this->parsedQuery);

		}
		catch (Exception $ex)
		{
			// Query is no select / insert / update / delete statement - handle it anyway
			//var_dump($ex);

		}

		self::analyze();

	}

	/*
	 * Static functions
	 */

	/*
	 * Splits a query
	 *
	 * @param string $_query
	 * @param string $_delimiter
	 */
	public static function split($_query, $_delimiter = ';')
	{
		$queries = preg_split("/".$_delimiter."+(?=([^'|^\\\']*['|\\\'][^'|^\\\']*['|\\\'])*[^'|^\\\']*[^'|^\\\']$)/", $_query);

		// Remove delimiter at the last query
		$queries[count($queries)-1] = preg_replace('/'.$_delimiter.'$/', '', $queries[count($queries)-1]);
		return $queries;
	}


	/*
	 * Strips all comments out
	 */
	public function stripComments() {

		$comments = array();

		// Inline comments
		preg_match_all('/(#|--\40)(.*)($|\n)/i', $this->query, $single);
		$comments = $single[0];

		// Other comments
		preg_match_all('/\/\*(.+)\*\//is', $this->query, $multi);
		$comments = array_merge($comments, $multi[0]);

		// Strip them
		$this->query = str_replace($comments, "\n", $this->query);

	}

	public function analyze() {

	}

	/*
	 * MANIPULATION
	 */

	public function applyCalculateFoundRows() {

		if($this->parsedQuery)
		{
			$this->parsedQuery['ColumnNames'][0] = 'SQL_CALC_FOUND_ROWS ' . $this->parsedQuery['ColumnNames'][0];
		}
		else
		{
			// @todo programming
		}

		//$this->parsedQuery['ColumnNames'] = array_merge(array('SQL_CALC_FOUND_ROWS'), (array)$this->parsedQuery['ColumnNames']);

		/*
		preg_match('/select\s+(.*)\s+from/i', $this->query, $select);

		if(isset($select[1]))
		{
			$this->query = str_replace($select[1], 'SQL_CALC_FOUND_ROWS ' . $select[1], $this->query);
		}
		*/


	}

	public function applyLimit($length, $start=0, $_applyToOriginal = false) {

		if($this->parsedQuery)
		{
			$this->parsedQuery['Limit'] = array(
				'Start' => $start,
				'Length' => $length,
			);

			if($_applyToOriginal)
				$this->parsedOriginalQuery['Limit'] = $this->parsedQuery['Limit'];

		}
		else
		{
			throw new NotImplementedException();
		}

		/*
		$this->query .= "\n\t" . 'LIMIT ' . $offset . ', ' . $limit;

		if($_applyToOriginal)
			$this->originalQuery .= "\n\t" . 'LIMIT ' . $offset . ', ' . $limit;
		*/

	}

	public function applySort($_sorting, $_applyToOriginal = false) {

		if($this->parsedQuery)
		{
			$this->parsedQuery['SortOrder'] = $_sorting;

			if($_applyToOriginal)
				$this->parsedOriginalQuery['SortOrder'] = $this->parsedQuery['SortOrder'];
		}
		else
		{
			throw new NotImplementedException();
		}
		/*

		$_sql = "\n\t" . trim($_sql);

		preg_match('/\s+?limit\s+(\d+),?\s+?(\d+)?/ims', $this->query, $limit);
		$this->query = str_replace($limit[0], $_sql . $limit[0], $this->query);

		if($_applyToOriginal)
			$this->originalQuery = str_replace($limit[0], $_sql .  $limit[0], $this->originalQuery);

		*/

	}


	public function getDatabase() {

		preg_match('/use (\w+)/', $this->query, $res);
		return $res[1];

	}

	private function stripEmptyLines(&$_string)
	{
		$_string = preg_replace('/\n\s*\n/', "\n", $_string);
	}

	/*
	 * Returns the type of the query
	 */
	public function getType()
	{
		if($this->parsedQuery)
		{
			return strtolower($this->parsedQuery['Command']);
		}
		else
		{
			preg_match('/^(\s*)(\w+)/', $this->query, $res);
			return strtolower($res[2]);
		}
	}

	public function getLimit()
	{
		if($this->parsedQuery)
		{
			return isset($this->parsedQuery['Limit']) ? $this->parsedQuery['Limit'] : false;
		}
		else
		{
			return null;
		}
	}

	public function returnsResultset()
	{
		if(in_array($this->getType(), $this->resultSetTypes))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/*
	 * Returns parsed query
	 */
	public function getQuery()
	{

		if($this->parsedQuery)
		{
			try
			{
				return $this->sqlCompiler->compile($this->parsedQuery);
			}
			catch(Exception $ex)
			{
				return $this->query;
			}
		}
		else
		{
			self::stripEmptyLines($this->query);
			return $this->query;
		}

	}

	public function getOriginalQuery()
	{

		if($this->parsedQuery)
		{
			try
			{
				return $this->sqlCompiler->compile($this->parsedOriginalQuery);
			}
			catch(Exception $ex)
			{
				return $this->originalQuery;
			}
		}
		else
		{
			self::stripEmptyLines($this->originalQuery);
			return $this->originalQuery;
		}
	}


}

?>