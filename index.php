<?php

	header("Content-Type: application/json");

	// 屏蔽报错
	error_reporting(0);
	
	// 数据库相关信息
	define("HOST", "localhost");
	define("USER", "root");
	define("PASS", "root");
	define("DBNAME", "mysql");

	// 连接数据库
	function connect() {
		$conn = new mysqli(HOST, USER, PASS, DBNAME);
		if (mysqli_connect_errno($conn)) {
			$arr = array(
				'code' => 500,
				'message' => mysqli_connect_error()
			);
			header("HTTP/1.1 500 Internal Server Error");
			echo json($arr);
			exit();
		}
		return $conn;
	}

	// 检查是否打开日志
	function check_log_status() {
		$conn = connect();
		$sql = "show variables like '%general_log%';";
		$result = $conn->query($sql);
		if (!$result) {
			$arr = array(
				'code' => 500,
				'message' => mysqli_error($conn)
			);
			header("HTTP/1.1 500 Internal Server Error");
			echo json($arr);
			exit();
		}
		$row = mysqli_fetch_array($result, MYSQL_ASSOC);
		$arr = array(
			'code' => 200,
			'message' => 'success',
			'result' => $row
		);
		echo json($arr);
		exit();
	}
	
	// 开启log记录
	function start_log() {
		$conn = connect();
		$sql = "set global general_log = on;";
		$result = $conn->query($sql);
		$sql = "set global log_output = 'table';";
		$result = $conn->query($sql);
		if (!$result) {
			$arr = array(
				'code' => 500,
				'message' => mysqli_error($conn)
			);
			header("HTTP/1.1 500 Internal Server Error");
			echo json($arr);
			exit();
		}
		$arr = array(
			'code' => 200,
			'message' => 'success'
		);
		echo json($arr);
		exit();

	}

	// 删除所有log
	function del_all_log() {
		$conn = connect();
		$sql = "truncate table general_log;";
		$result = $conn->query($sql);
		if (!$result) {
			$arr = array(
				'code' => 500,
				'message' => mysqli_error($conn)
			);
			header("HTTP/1.1 500 Internal Server Error");
			echo json($arr);
			exit();
		}

		$arr = array(
			'code' => 200,
			'message' => 'success'
		);
		echo json($arr);
		exit();
	}

	// 获取所有log
	function get_all_log() {
		$conn = connect();
		$sql = 'select * from general_log order by event_time desc;';
		$result = $conn->query($sql);
		if (!$result) {
			$arr = array(
				'code' => 500,
				'message' => mysqli_error($conn)
			);
			header("HTTP/1.1 500 Internal Server Error");
			echo json($arr);
			exit();
		}
		
		$filter = array('Connect', 'Quit', 'Init DB');
		$arr = array();
		while ($row = mysqli_fetch_array($result, MYSQL_ASSOC)) {
			$command_type = $row['command_type'];
			$argument = $row['argument'];
			if (!in_array($command_type, $filter) && $argument != substr($sql, 0, -1)) {
				if (substr($argument, 0, 10) != 'set global') {
					$arr[] = $row;
				}
			}
			
		}

		$arr = array(
			'code' => 200,
			'result' => $arr
		);
		echo json($arr);
		exit();
	}

	function json($result) {
		if (!is_array($result)) {
			echo '不是合法数组';
			return '';
		}
		return json_encode($result);
	}

	// 合法操作
	$l = array('check_log_status', 'start_log', 'del_all_log', 'get_all_log');

	$act = isset($_REQUEST['act']) ? $_REQUEST['act'] : 'get_all_log';

	// 检查参数
	if (!in_array($act, $l)) {
		header("HTTP/1.1 400 Bad Request");
		echo json(array('code' => 400, 'message' => 'Bad Request'));
		exit();
	}

	$act();

