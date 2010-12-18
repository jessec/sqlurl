<?php

class Urlsql{

	/**
	 *
	 *
	 * @var url :
	 * 
	 *  when using a comma the url needs to be escaped , = '%2C'
	 *
	 *  select : /?/users/s/name,status,age/f/users/w/id.eq.4,name.lt.5/l/2,2/o/name,status.desc
	 *
	 *	insert : /?/a:users/c:members/f:color/i:user/v:name.robert,age.age.56
	 *
	 *	delete : /?/a:users/c:members/f:color/d:user/w:name.eq.3,age.gt.8
	 *
	 *	update : /?/a:users/c:members/f:color/u:user/v:name.robert,age.age.56/w:name.eq.3,age.gt.8
	 *
	 *	Usage :
	 *
	 *  $uql = new Url2sql;
	 *	$p['url'] = $url;
	 *	$uql->setParam($p);
	 *	echo $uql->getSql();
	 *
	 */

	private $sql;
	private $rawUrl;
	private $url = array();
	private $select;
	private $table;
	private $update;
	private $insert;
	private $limit;
	private $order;
	private $from;
	private $app;
	private $class;
	private $functions;
	private $action;


	function setParam($p){

		$this->validate($p['url']);
		$this->rawUrl = $p['url'];
		$this->processUrl($p['url']);
	}

	function processUrl($url){
		$array = explode('/',$url);
		foreach ($array as $item){

			$action = substr($item, 0, 1);
			$raw = substr($item, 2);

			switch ($action) {
				case 's':
					$this->select = $this->getSelect($raw);
					$this->action = 's';
					break;
				case 'i':
					$this->table = $raw;
					$this->action = 'i';
					break;
				case 'u':
					$this->update = $this->getUpdate($raw);
					$this->action = 'u';
					break;
				case 'd':
					$this->table = $this->getDelete($raw);
					$this->action = 'd';
					break;
				case 't':
					$this->table = $this->getTable($raw);
					break;
				case 'o':
					$this->order = $this->getOrder($raw);
					break;
				case 'l':
					$this->limit = $this->getLimit($raw);
					break;
				case 'w':
					$this->where = $this->getWhere($raw);
					break;
				case 'v':
					$this->values = $this->assocDict($raw);
					break;
				case 'a':
					$this->app = $raw;
					break;
				case 'f':
					// function chain
					break;
				case 'c':
					// class
					break;
				default:
					//echo $item[0].' : '.$raw;
			}
		}
	}

	function ctr(){
		return array('a' => $this->app);
	}

	function validate($url){
		return $url;
	}

	function getOperators(){
		$op['eq'] = '=';
		$op['lt'] = '<';
		$op['gt'] = '>';
		return $op;
	}

	function assocDict($d){
		/*
		 *  @var $d = name.robert,age.6
		 *
		 *  @result {'name' => 'robert',..}
		 *
		 */
		$d = explode(',', $d);
		$list = array();
		foreach ($d as $item){
			$item = explode('.',$item);
			$list[$item[0]] = $item[1];
		}
		return $list;
	}

	function tuple($tuple){

		/*
		 * to check values
		 *
		 */

		$tuple = explode(',', $tuple);
		$list = array();
		foreach ($tuple as $item){
			array_push($list, $item);
		}
		return implode(', ', $list);
	}

	function whereClause($clause){
		$op = $this->getOperators();
		preg_match('/[^.]+\.[^.]+\./', $clause, $matches);
		$key_op = explode('.', $matches[0]);
		return $key_op[0].$op[$key_op[1]].'\''.addslashes(urldecode(substr($clause, strlen($matches[0])))).'\'';
	}

	function whereDict($raw){
		$result = array();
		$raw = explode(',',$raw);
		foreach ($raw as $item){
			array_push($result, $this->whereClause($item));
		}
		return implode(' and ', $result);
	}

	function getUpdate($u){}

	function getDelete($d){
		return $d;
	}

	function getFrom($f){}

	function getSelect($s){
		return $this->select = 'select '.$this->tuple($s).' from ';
	}

	function getTable($t){
		return $t;
	}

	function getWhere($w){
		return ' where '.$this->whereDict($w);
	}

	function getLimit($l){
		return ' limit '.$l.' ';
	}

	function getOrder($o){
		$o = explode('.',$o);
		return ' order by '.$this->tuple($o[0]).' '.$o[1].' ';
	}

	function getFields($f){
		return $this->tuple($f);
	}

	function getTable($t){
		return $t;
	}

	function selectQuery(){
		return "$this->select $this->table $this->where $this->limit $this->order";
	}

	function getUpdateValues(){
		$result = array();
		foreach ($this->values as $key => $value){
			array_push($result, $key.' = \''.addslashes(urldecode($value)).'\'');
		}
		$result = implode(' , ', $result);
		return $result;
	}

	function updateQuery(){
		return sprintf('UPDATE SET %s %s', $this->getUpdateValues(), $this->where);
	}

	function insertQuery(){
		
		$values = array_map('addslashes', $this->values);
		$values = array_map('urldecode', $this->values);
		
		return sprintf(
			'INSERT INTO %s (%s) VALUES ("%s")', 
		$this->table,
		implode(', ', array_map('addslashes', array_keys($this->values))),
		implode('", "',$values));
	}

	function deleteQuery(){
		return "delete from $this->table $this->where";
	}

	function getSql(){

		switch ($this->action) {
			case 's':
				return $this->selectQuery();
				break;
			case 'i':
				return $this->insertQuery();
				break;
			case 'u':
				return $this->updateQuery();
				break;
			case 'd':
				return $this->deleteQuery();
				break;
		}


	}

}
