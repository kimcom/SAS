<?php
class Cnn {
	private $db = null;
	public function __construct() {
		try {
			$this->db = new PDO('mysql:host=localhost;dbname='.$_SESSION['dbname'].';port='.$_SESSION['server_port'], $_SESSION['server_user'], $_SESSION['server_pass'],array(1006));
			//$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch (PDOException $e) {
			Fn::errorToLog("PDO error!: ", $e->getMessage());
			die();
		}
	}
	
//user login and register
	public function login($login, $pass) {
		$_SESSION['error_msg'] = "<h4 class='center list-group-item list-group-item-danger'>ВНИМАНИЕ!<br><small>Неверно введен e-mail или пароль!</small></h4>";
		$stmt = $this->db->prepare("CALL pr_login_site('login', @id, ?, ?, ?, ?, ?, ?, ?, ?)");
		$stmt->bindParam(1, $login, PDO::PARAM_STR);
		$stmt->bindParam(2, $pass, PDO::PARAM_STR);
		$stmt->bindParam(3, $email, PDO::PARAM_STR);
		$stmt->bindParam(4, $fio, PDO::PARAM_STR);
		$stmt->bindParam(5, $phone, PDO::PARAM_STR);
		$stmt->bindParam(6, $company, PDO::PARAM_STR);
		$stmt->bindParam(7, $post, PDO::PARAM_STR);
		$stmt->bindParam(8, $codeauth, PDO::PARAM_STR);
// вызов хранимой процедуры
		$stmt->execute();
		if (!Fn::checkErrorMySQLstmt($stmt)) return false;
		$r = $stmt->fetch(PDO::FETCH_BOTH);
		if (!$r) return false;
		if ($stmt->rowCount()==0) return false;
//Fn::debugToLog("login", json_encode($r));
		if ($r[AccessLevel]==-1){
			$_SESSION['error_msg'] = "<h4 class='center list-group-item list-group-item-danger'>"
					. "ВНИМАНИЕ!<br><small>Вы не активировали Ваш аккаунт!<br>"
					. "Вход в систему возможен только после активации!</small></h4>";
			return false;
		}
		$_SESSION['UserID'] = $r[UserID];
		$_SESSION['UserName'] = $r[UserName];
		$_SESSION['UserEMail'] = $r[EMail];
		$_SESSION['UserPost'] = $r[Position];
		$_SESSION['ClientID'] = $r[ClientID];
		$_SESSION['ClientName'] = $r[ClientName];
		$_SESSION['CompanyName'] = $r[CompanyName];
		$_SESSION['AccessLevel'] = $r[AccessLevel];
		$_SESSION['access'] = true;
		$_SESSION['error_msg'] = "";
		return true;
	}
	public function registration($login, $email, $pass, $fio, $company, $phone, $post) {
		$codeauth = rand(1111111111, 9999999999);
		$company = "Сузирье™";
//проверяем валидность e-mail
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$_SESSION['error_msg'] = "<h4 class='center list-group-item list-group-item-danger m0'>"
				. "ВНИМАНИЕ!<br><small>Указан неверный e-mail!</small></h4>";
			return false;
		}
//вызов хранимой процедуры
		$stmt = $this->db->prepare("CALL pr_login_site('register', @id, ?, ?, ?, ?, ?, ?, ?, ?)");
		$stmt->bindParam(1, $login, PDO::PARAM_STR);
		$stmt->bindParam(2, $pass, PDO::PARAM_STR);
		$stmt->bindParam(3, $email, PDO::PARAM_STR);
		$stmt->bindParam(4, $fio, PDO::PARAM_STR);
		$stmt->bindParam(5, $phone, PDO::PARAM_STR);
		$stmt->bindParam(6, $company, PDO::PARAM_STR);
		$stmt->bindParam(7, $post, PDO::PARAM_STR);
		$stmt->bindParam(8, $codeauth, PDO::PARAM_STR);
		$stmt->execute();
		if (!Fn::checkErrorMySQLstmt($stmt)) {
			$_SESSION['error_msg'] = "<h4 class='center list-group-item list-group-item-danger m0'>"
					. "ВНИМАНИЕ!<br><small>Ошибка при регистрации пользователя!</small></h4>";
			return false;
		}
		$result = $stmt->fetch(PDO::FETCH_BOTH);
		if (!$result) return false;
		//echo 'result='.$result[0].'<br>';
		if ($result[0] < 0) {
			$_SESSION['error_msg'] = "<h4 class='center list-group-item list-group-item-danger m0'>"
				. "ВНИМАНИЕ!<br><small>Пользователь с указанным именем уже зарегистрирован!</small></h4>";
			return false;
		}
		if ($result[0] == 0) {
			$_SESSION['error_msg'] = "<h4 class='center list-group-item list-group-item-danger m0'>"
				. "ВНИМАНИЕ!<br><small>Не удалось зарегистрировать пользователя!</small></h4>";
			return false;
		}
		if ($result[0] > 0) {
//оправляем сообщение пользователю
			$subject = 'Регистрация аккаунта в инф. системе ';
			$message = "
Здравствуйте, ".$fio."!

Ваш email был зарегистрирован в информационной системе ".$_SESSION['company']."

Для полноценной работы в нашей системе Вам необходимо активировать 
Ваш аккаунт перейдя по ссылке: http://" . $_SERVER['HTTP_HOST'] . "/register_ok/activate?auth=" . $codeauth . "

После активации Вы сможете войти в информационную систему.

Если вы получили это сообщение по ошибке, не предпринимайте никаких действий. 

Если вы не нажмете эту ссылку, то адрес не будет добавлен в аккаунт.

Успехов!
------------------
admin@" . $_SERVER['HTTP_HOST'] . "
";
			$sended = Mail::smtpmail($email, $fio, $subject, $message);
			if (!$sended) {
				$_SESSION['error_msg'] = "<h4 class='center list-group-item list-group-item-danger m0'>"
						. "ВНИМАНИЕ!<br><small>При отправке сообщения по e-mail возникли проблемы!</small></h4>";
				return false;
			}
			$sended = Mail::smtpmail($_SESSION['adminEmail'], $fio, $subject, $message.'E-mail:'.$email);
			return true;
		}
	}
	public function registration_ok($codeauth) {
//вызов хранимой процедуры
		$stmt = $this->db->prepare("CALL pr_login_site('register_ok', @id, ?, ?, ?, ?, ?, ?, ?, ?)");
		$stmt->bindParam(1, $login, PDO::PARAM_STR);
		$stmt->bindParam(2, $pass, PDO::PARAM_STR);
		$stmt->bindParam(3, $email, PDO::PARAM_STR);
		$stmt->bindParam(4, $fio, PDO::PARAM_STR);
		$stmt->bindParam(5, $phone, PDO::PARAM_STR);
		$stmt->bindParam(6, $company, PDO::PARAM_STR);
		$stmt->bindParam(7, $post, PDO::PARAM_STR);
		$stmt->bindParam(8, $codeauth, PDO::PARAM_STR);
		$stmt->execute();
		if (!Fn::checkErrorMySQLstmt($stmt)) return false;
		$result = $stmt->fetch(PDO::FETCH_BOTH);
		if (!$result) return false;
		if ($result[0] == 0) {
			$_SESSION['error_msg'] = "<h4 class='center list-group-item list-group-item-danger m0'>"
				. "<br>ВНИМАНИЕ!<br><br><small>Возникла ошибка при активации аккаунта!<br><br>"
				. "Сообщите разработчику!<br><br></small></h4>";
			return false;
		}
		if ($result[0] > 0) {
//оправляем сообщение пользователю
			$email = $result['EMail'];
			$fio = $result[2];
			$subject = "Активация аккаунта успешно завершена!";
			$message = "
Добро пожаловать, " . $fio . "!

Ваш аккаунт был успешно активирован в информационной системе " . $_SESSION['company'] . "

Вы можете войти в систему по адресу http://" . $_SERVER['HTTP_HOST'] . "/logon

Если вы получили это сообщение по ошибке, не предпринимайте никаких действий. 

Успехов!
------------------
admin@" . $_SERVER['HTTP_HOST'] . "
";
			$sended = Mail::smtpmail($email, $fio, $subject, $message);
			if (!$sended) {
				$_SESSION['error_msg'] = "<h4 class='center list-group-item list-group-item-danger m0'>"
						. "ВНИМАНИЕ!<br><small>При отправке сообщения по e-mail возникли проблемы!</small></h4>";
				return false;
			}
			$sended = Mail::smtpmail($_SESSION['adminEmail'], $fio, $subject, $message.'E-mail:'. $email);
			return true;
		}
	}
	public function recovery($email) {
//вызов хранимой процедуры
		$stmt = $this->db->prepare("CALL pr_login_site('recovery', @id, ?, ?, ?, ?, ?, ?, ?, ?)");
		$stmt->bindParam(1, $login, PDO::PARAM_STR);
		$stmt->bindParam(2, $pass, PDO::PARAM_STR);
		$stmt->bindParam(3, $email, PDO::PARAM_STR);
		$stmt->bindParam(4, $fio, PDO::PARAM_STR);
		$stmt->bindParam(5, $phone, PDO::PARAM_STR);
		$stmt->bindParam(6, $company, PDO::PARAM_STR);
		$stmt->bindParam(7, $post, PDO::PARAM_STR);
		$stmt->bindParam(8, $codeauth, PDO::PARAM_STR);
		$stmt->execute();
		if (!Fn::checkErrorMySQLstmt($stmt)) return false;
		$result = $stmt->fetch(PDO::FETCH_BOTH);
		if (!$result) return false;
		if ($result[0] == 0) {
			$_SESSION['error_msg'] = "<h4 class='center list-group-item list-group-item-danger m0'>"
				. "<br>ВНИМАНИЕ!<br><br><small>E-mail ".$email." не найден!<br><br>"
				. "Возможно Вы неправильно указали e-mail!<br><br></small></h4>";
			return false;
		}
		if ($result[0] > 0) {
//оправляем сообщение пользователю
			$fio = $result[2];
			$subject = 'Восстановление пароля для доступа к информационной системе ' . $_SESSION['company'];
			$message = "
Здравствуйте, " . $fio . "!

Вы можете войти в систему по адресу http://" . $_SERVER['HTTP_HOST'] . "/logon

Ваш пароль: " . $result[1] . "

Если вы получили это сообщение по ошибке, не предпринимайте никаких действий. 

Успехов!
------------------
admin@" . $_SERVER['HTTP_HOST'] . "
";
			$sended = Mail::smtpmail($email, $fio, $subject, $message);
			if (!$sended) {
				$_SESSION['error_msg'] = "<h4 class='center list-group-item list-group-item-danger m0'>"
						. "ВНИМАНИЕ!<br><small>При отправке сообщения по e-mail возникли проблемы!</small></h4>";
				return false;
			}
			$sended = Mail::smtpmail($_SESSION['adminEmail'], $fio, $subject, $message . 'E-mail:' . $email);
			return true;
		}
	}

//reports
	public function get_report1_data() {
		foreach ($_REQUEST as $arg => $val) ${$arg} = $val;
		//echo $DT_start.'<br>';
		if(isset($DT_start)){
			$dt = DateTime::createFromFormat('d?m?Y', $DT_start);
		//echo $dt->format('Ymd');
			$date1 = $dt->format('Ymd');
		}
		if (isset($DT_stop)) {
			$dt = DateTime::createFromFormat('d?m?Y', $DT_stop);
			$date2 = $dt->format('Ymd');
		}
Fn::debugToLog('report4 user:' . $_SESSION['UserName'], urldecode($_SERVER['QUERY_STRING']));
Fn::debugToLog('report4 user:' . $_SESSION['UserName'], "".$date1."	".  $date2);
		//call pr_reports('avg_sum', @_id, '20141001', '20141031', '');
		$stmt = $this->db->prepare("CALL pr_reports('avg_sum', @id, ?, ?, null)");
		$stmt->bindParam(1, $date1, PDO::PARAM_STR);
		$stmt->bindParam(2, $date2, PDO::PARAM_STR);
		//$stmt->bindParam(3, '', PDO::PARAM_STR);
		//$stmt->bindParam(3, urldecode($_SERVER['QUERY_STRING']), PDO::PARAM_STR);
// вызов хранимой процедуры
		$stmt->execute();
		if (!Fn::checkErrorMySQLstmt($stmt))
			return false;
//		$r = $stmt->fetch(PDO::FETCH_BOTH);
//		if (!$r)
//			return false;
		if ($stmt->rowCount() == 0)
			return false;
		$response = new stdClass();
//		$response->draw = 1;
//		$response->recordsTotal = 28;
//		$response->recordsFiltered = 28;
		do {
			$rowset = $stmt->fetchAll(PDO::FETCH_BOTH);
			if ($rowset) {
				$i = 0;
				foreach ($rowset as $row) {
					$response->data[$i] = array(
												$row['ClientID'],
												$row['NameShort'],
												$row['City'],
												$row['Avg_Sum'],
												$row['Avg_Check'],
												$row['DT_start'],
												$row['DT_stop'],
												$row['DayWork']
					);
					$i++;
				}
			}
		} while ($stmt->nextRowset());
		//header("Content-type: application/json;charset=utf8");
		return json_encode($response);
	}
	public function get_report2_data() {
		foreach ($_REQUEST as $arg => $val) ${$arg} = $val;
		//echo $DT_start.' '.  $DT_stop . '<br>';
		if(isset($DT_start)){
			$dt = DateTime::createFromFormat('d?m?Y', $DT_start);
		//echo $dt->format('Ymd').'<br>';
			$date1 = $dt->format('Ymd');
		}else{return;}
		if (isset($DT_stop)) {
			$dt = DateTime::createFromFormat('d?m?Y', $DT_stop);
		//echo $dt->format('Ymd');
			$date2 = $dt->format('Ymd');
		}else{return;}
		//call pr_reports('avg_sum', @_id, '20141001', '20141031', '');
		$stmt = $this->db->prepare("CALL pr_reports('sale_Trixie', @id, ?, ?, null)");
		$stmt->bindParam(1, $date1, PDO::PARAM_STR);
		$stmt->bindParam(2, $date2, PDO::PARAM_STR);
		//$stmt->bindParam(3, "nnn", PDO::PARAM_STR);
		//$stmt->bindParam(3, urldecode($_SERVER['QUERY_STRING']), PDO::PARAM_STR);
// вызов хранимой процедуры
		$stmt->execute();
		if (!Fn::checkErrorMySQLstmt($stmt))
			return false;
//		$r = $stmt->fetch(PDO::FETCH_BOTH);
//		if (!$r)
//			return false;
		if ($stmt->rowCount() == 0)
			return false;
		$response = new stdClass();
//		$response->draw = 1;
//		$response->recordsTotal = 28;
//		$response->recordsFiltered = 28;
		do {
			$rowset = $stmt->fetchAll(PDO::FETCH_BOTH);
			if ($rowset) {
				$i = 0;
				foreach ($rowset as $row) {
					$response->data[$i] = array(
												$row['PointName'],
												$row['SellerName'],
												$row['GoodArticle'],
												$row['GoodName'],
												$row['Quantity'],
												$row['Sebest'],
												$row['Oborot'],
												$row['Dohod'],
												$row['Percent'],
					);
					$i++;
				}
			}
		} while ($stmt->nextRowset());
		header("Content-type: application/json;charset=utf8");
		return json_encode($response);
	}
	public function get_report4_data() {
		foreach ($_REQUEST as $arg => $val) ${$arg} = $val;
Fn::debugToLog('report4 user:' . $_SESSION['UserName'], urldecode($_SERVER['QUERY_STRING']));
//Fn::paramToLog();  
//echo $DT_start.' '.  $DT_stop . '<br>';
		if (isset($DT_start)) {
			$dt = DateTime::createFromFormat('d?m?Y', $DT_start);
			//echo $dt->format('Ymd').'<br>';
			$date1 = $dt->format('Ymd');
		} else {
			return;
		}
		if (isset($DT_stop)) {
			$dt = DateTime::createFromFormat('d?m?Y', $DT_stop);
			//echo $dt->format('Ymd');
			$date2 = $dt->format('Ymd');
		} else {
			return;
		}
		$action = '';
		if ($sid=='4')  $action = 'sale';
		if ($sid=='42') $action = 'sale42';
//Fn::debugToLog("report4", 'action='.$action);
		//$url = 'ddd=1&'.urldecode($_SERVER['QUERY_STRING']);
		//call pr_reports('avg_sum', @_id, '20141001', '20141031', '');
		$stmt = $this->db->prepare("CALL pr_reports(?, @id, ?, ?, ?)");
		$stmt->bindParam(1, $action, PDO::PARAM_STR);
		$stmt->bindParam(2, $date1, PDO::PARAM_STR);
		$stmt->bindParam(3, $date2, PDO::PARAM_STR);
		$stmt->bindParam(4, urldecode($_SERVER['QUERY_STRING']), PDO::PARAM_STR);
// вызов хранимой процедуры
		$stmt->execute();
		header("Content-type: application/json;charset=utf8");
		$response = new stdClass();
		$response->page = 1;
		$response->total = 1;
		$response->records = 0;
		$response->error = '';
		if (!Fn::checkErrorMySQLstmt($stmt)) $response->error = $stmt->errorInfo();
//	Fn::debugToLog("resp", json_encode($response));
		if ($stmt->rowCount() > 0){
			$t = 0;
			do {
				$rowset = $stmt->fetchAll();
				if ($rowset!=null) {
					if($t==1){
						foreach ($rowset as $row) {
	//			Fn::debugToLog($t, $row[0]);
							$response->query = $row[0];
							$response->records = $row[1];
						}
					}else if ($t == 0) {
				//Fn::debugToLog("columnCount 2", $stmt->columnCount());
						$columnCount = $stmt->columnCount();
						$i = 0;
						foreach ($rowset as $row) {
							$response->rows[$i]['id'] = $row[0];
							$ar = array();
							for($f=0;$f<$columnCount-5;$f++){
								$ar[] = $row[$f];
							}
							$ar = array_pad($ar,10,null);
							for ($f = $columnCount - 5; $f < $columnCount; $f++) {
								$ar[] = $row[$f];
							}
							$response->rows[$i]['cell'] = $ar;
							$i++;
						}
					}
				}
				$t++;
			} while ($stmt->nextRowset());
		}
//Fn::debugToLog("report4", json_encode($response));
		echo json_encode($response);
	}
	public function get_report5_data() {
		foreach ($_REQUEST as $arg => $val)
			${$arg} = $val;
//Fn::debugToLog('report5 user:'.  $_SESSION['UserName'], urldecode($_SERVER['QUERY_STRING']));
//Fn::debugToLog('REQUEST_URI', urldecode($_SERVER['REQUEST_URI']));
Fn::debugToLog('REQUEST_URI', urldecode($_SERVER['QUERY_STRING']));
		$stmt = $this->db->prepare("CALL pr_reports('goods', @id, ?, ?, ?)");
		$stmt->bindParam(1, $date1, PDO::PARAM_STR);
		$stmt->bindParam(2, $date2, PDO::PARAM_STR);
		$stmt->bindParam(3, urldecode($_SERVER['QUERY_STRING']), PDO::PARAM_STR);
// вызов хранимой процедуры
		$stmt->execute();
		header("Content-type: application/json;charset=utf8");
		$response = new stdClass();
		$response->page = 1;
		$response->total = 1;
		$response->records = 0;
		$response->query = "";
		$response->error = '';
		if (!Fn::checkErrorMySQLstmt($stmt)) $response->error = $stmt->errorInfo();
		//	Fn::debugToLog("resp", json_encode($response));
		if ($stmt->rowCount() > 0){
			$t = 0;
			do {
				$rowset = $stmt->fetchAll();
				if ($rowset != null) {
					if ($t == 1) {
						foreach ($rowset as $row) {
	//			Fn::debugToLog($t, $row[0]);
							$response->query = $row[0];
							$response->records = $row[1];
						}
					} else if ($t == 0) {
						//Fn::debugToLog("columnCount 2", $stmt->columnCount());
						$columnCount = $stmt->columnCount();
						$i = 0;
						foreach ($rowset as $row) {
							$response->rows[$i]['id'] = $row[0];
							$ar = array();
							for ($f = 0; $f < $columnCount - 7; $f++) {
								$ar[] = $row[$f];
							}
							$ar = array_pad($ar, 8, null);
							for ($f = $columnCount - 7; $f < $columnCount; $f++) {
								$ar[] = $row[$f];
							}
							$response->rows[$i]['cell'] = $ar;
							$i++;
						}
					}
				}
				$t++;
			} while ($stmt->nextRowset());
		}
		echo json_encode($response);
	}
	public function get_report6_data() {
		foreach ($_REQUEST as $arg => $val)
			${$arg} = $val;
//Fn::debugToLog('report5 user:'.  $_SESSION['UserName'], urldecode($_SERVER['QUERY_STRING']));
//Fn::debugToLog('REQUEST_URI', urldecode($_SERVER['REQUEST_URI']));
Fn::debugToLog('REQUEST_URI', urldecode($_SERVER['QUERY_STRING']));
		$stmt = $this->db->prepare("CALL pr_reports('goods_action', @id, ?, ?, ?)");
		$stmt->bindParam(1, $date1, PDO::PARAM_STR);
		$stmt->bindParam(2, $date2, PDO::PARAM_STR);
		$stmt->bindParam(3, urldecode($_SERVER['QUERY_STRING']), PDO::PARAM_STR);
// вызов хранимой процедуры
		$stmt->execute();
		header("Content-type: application/json;charset=utf8");
		$response = new stdClass();
		$response->page = 1;
		$response->total = 1;
		$response->records = 0;
		$response->query = "";
		$response->error = '';
		if (!Fn::checkErrorMySQLstmt($stmt))
			$response->error = $stmt->errorInfo();
		//	Fn::debugToLog("resp", json_encode($response));
		if ($stmt->rowCount() > 0) {
			$t = 0;
			do {
				$rowset = $stmt->fetchAll();
				if ($rowset != null) {
					if ($t == 1) {
						foreach ($rowset as $row) {
							//			Fn::debugToLog($t, $row[0]);
							$response->query = $row[0];
							$response->records = $row[1];
						}
					} else if ($t == 0) {
						//Fn::debugToLog("columnCount 2", $stmt->columnCount());
						$columnCount = $stmt->columnCount();
						$start_col = 3;
						$max_col = 15;
						$i = 0;
						foreach ($rowset as $row) {
							$response->rows[$i]['id'] = $row[0];
							$ar = array();
							for ($f = 0; $f < $start_col; $f++) {
								$ar[] = $row[$f];
							}
							$ar = array_pad($ar, $max_col - $columnCount + $start_col, null);
							for ($f = $start_col; $f < $columnCount; $f++) {
								$ar[] = $row[$f];
							}
							$response->rows[$i]['cell'] = $ar;
//Fn::debugToLog("ar", json_encode($ar));
							$i++;
						}
					}
				}
				$t++;
			} while ($stmt->nextRowset());
		}
		header("Content-type: application/json;charset=utf8");
		echo json_encode($response);
	}
	public function get_report7_data() {
		foreach ($_REQUEST as $arg => $val)
			${$arg} = $val;
Fn::debugToLog('report7 user:' . $_SESSION['UserName'], urldecode($_SERVER['QUERY_STRING']));
		//echo $DT_start.' '.  $DT_stop . '<br>';
		if (isset($DT_start)) {
			$dt = DateTime::createFromFormat('d?m?Y', $DT_start);
			//echo $dt->format('Ymd').'<br>';
			$date1 = $dt->format('Ymd');
		} else {
			return;
		}
		if (isset($DT_stop)) {
			$dt = DateTime::createFromFormat('d?m?Y', $DT_stop);
			//echo $dt->format('Ymd');
			$date2 = $dt->format('Ymd');
		} else {
			return;
		}
//Fn::debugToLog('report', $date1.'	'.$date2);
//Fn::paramToLog();
		//$url = 'ddd=1&'.urldecode($_SERVER['QUERY_STRING']);
		//call pr_reports('avg_sum', @_id, '20141001', '20141031', '');
		$stmt = $this->db->prepare("CALL pr_reports('sale_opt', @id, ?, ?, ?)");
		$stmt->bindParam(1, $date1, PDO::PARAM_STR);
		$stmt->bindParam(2, $date2, PDO::PARAM_STR);
		//$stmt->bindParam(3, $url, PDO::PARAM_STR);
		$stmt->bindParam(3, urldecode($_SERVER['QUERY_STRING']), PDO::PARAM_STR);
// вызов хранимой процедуры
		$stmt->execute();
		header("Content-type: application/json;charset=utf8");
		$response = new stdClass();
		$response->page = 1;
		$response->total = 1;
		$response->records = 0;
		$response->error = '';
		if (!Fn::checkErrorMySQLstmt($stmt))
			$response->error = $stmt->errorInfo();
//	Fn::debugToLog("resp", json_encode($response));
		if ($stmt->rowCount() > 0) {
			$t = 0;
			do {
				$rowset = $stmt->fetchAll();
				if ($rowset != null) {
					if ($t == 1) {
						foreach ($rowset as $row) {
							//			Fn::debugToLog($t, $row[0]);
							$response->query = $row[0];
							$response->records = $row[1];
						}
					} else if ($t == 0) {
						//Fn::debugToLog("columnCount 2", $stmt->columnCount());
						$columnCount = $stmt->columnCount();
						$i = 0;
						foreach ($rowset as $row) {
							$response->rows[$i]['id'] = $row[0];
							$ar = array();
							for ($f = 0; $f < $columnCount - 5; $f++) {
								$ar[] = $row[$f];
							}
							$ar = array_pad($ar, 10, null);
							for ($f = $columnCount - 5; $f < $columnCount; $f++) {
								$ar[] = $row[$f];
							}
							$response->rows[$i]['cell'] = $ar;
							$i++;
						}
					}
				}
				$t++;
			} while ($stmt->nextRowset());
		}
		return json_encode($response);
	}

	public function get_pendel_data2() {
		foreach ($_REQUEST as $arg => $val)
			${$arg} = $val;
Fn::debugToLog('pendel user:' . $_SESSION['UserName'], urldecode($_SERVER['QUERY_STRING']));
		if (isset($DT_start)) {
			$dt1 = DateTime::createFromFormat('d?m?Y', $DT_start);
			//echo $dt->format('Ymd').'<br>';
			$dateStart = $dt1->format('Ymd');
		} else {
			return;
		}
		if (isset($DT_stop)) {
			$dt2 = DateTime::createFromFormat('d?m?Y', $DT_stop);
			//echo $dt->format('Ymd');
			$dateStop = $dt2->format('Ymd');
		} else {
			return;
		}
//Fn::paramToLog();
		$response = new stdClass();
		$response->error = '';
		$response->table1 = '';
		$str = '';
		$part = 4;
		$cnt_tbl = 0;
		if($part == 4){
//Fn::debugToLog('pendel', $dateStart . '	' . $dateStop.'	'.$part);
//запрос валовый доход
			$stmt = $this->db->prepare("CALL pr_reports_pendel(?, @id, ?, ?)");
			$stmt->bindParam(1, $part, PDO::PARAM_STR);
			$stmt->bindParam(2, $dateStart, PDO::PARAM_STR);
			$stmt->bindParam(3, $dateStop, PDO::PARAM_STR);
			// вызов хранимой процедуры
			$stmt->execute();
			if (!Fn::checkErrorMySQLstmt($stmt))
				$response->error = $stmt->errorInfo();
			$gross = $stmt->fetchAll(PDO::FETCH_BOTH);
			if ($gross) {
				$dataGross = array();
				$columnCount = $stmt->columnCount();
				$rowCount = $stmt->rowCount();
				$dg_max_id_row = $gross[$rowCount - 1][0];
				$cnt_tbl = $dg_max_id_row - 1; //так как нет затрат по экспорту
//Fn::debugToLog('$dg_max_id_row', $dg_max_id_row.'	$cnt_tbl:'.$cnt_tbl);
				if ($dg_max_id_row != 0){
//конвертируем полученный rowset в удобный нам формат в array
					for ($dt = clone $dt1; $dt <= $dt2; $dt->modify('+1 month')) {
						$period = $dt->format('Y-m');
						$dataGross[$dg_max_id_row][$period]['Sebest'] = 0;
						$dataGross[$dg_max_id_row][$period]['Oborot'] = 0;
						$dataGross[$dg_max_id_row][$period]['Dohod'] = 0;
					}
					for ($tr = 0; $tr < $dg_max_id_row; $tr++) {
						for ($dt = clone $dt1; $dt <= $dt2; $dt->modify('+1 month')) {
							$period = $dt->format('Y-m');
							for ($row = 1; $row < $rowCount; $row++) {
								if ($gross[$row][0] != $tr + 1 || $period != $gross[$row]['Period']) continue;
								$dataGross[$tr]['Field1']['Name'] = $gross[$row]['Stream'];
								$dataGross[$tr][$period]['Period'] = $gross[$row]['Period'];
								$dataGross[$tr][$period]['CatID'] = $gross[$row]['CatID'];
								$dataGross[$tr][$period]['Sebest'] = $gross[$row]['Sebest'];
								$dataGross[$tr][$period]['Oborot'] = $gross[$row]['Oborot'];
								$dataGross[$tr][$period]['Percent'] = $gross[$row]['Percent'];
								$dataGross[$tr][$period]['Dohod'] = $gross[$row]['Dohod'];
								$dataGross[$dg_max_id_row]['Field1']['Name'] = 'Итого:';
								$dataGross[$dg_max_id_row][$period]['Sebest'] += $gross[$row]['Sebest'];
								$dataGross[$dg_max_id_row][$period]['Oborot'] += $gross[$row]['Oborot'];
								$dataGross[$dg_max_id_row][$period]['Dohod'] += $gross[$row]['Dohod'];
							}
						}
					}
				}
			}		
		}

//Fn::objectToLog($dataGross);

//запрос по затратам
//Fn::debugToLog('pendel', $dateStart . '	' . $dateStop.'	'.$action);
//Fn::debugToLog('pendel', 'dt1='.$dt1->format('Y-m') . '	dt2=' . $dt2->format('Y-m'));
		$spent = array();
		$stmt = $this->db->prepare("CALL pr_reports_pendel(?, @id, ?, ?)");
		$stmt->bindParam(1, $action, PDO::PARAM_STR);
		$stmt->bindParam(2, $dateStart, PDO::PARAM_STR);
		$stmt->bindParam(3, $dateStop, PDO::PARAM_STR);
		$stmt->execute();
		if (!Fn::checkErrorMySQLstmt($stmt))
			$response->error = $stmt->errorInfo();
		$t = 0;
		do {
			$rowset = $stmt->fetchAll(PDO::FETCH_BOTH);
			if ($rowset) {
				$spent[$t] = array(); 
				$columnCount = $stmt->columnCount();
				$rowCount = $stmt->rowCount();
				$spent[$t]['name'] = $rowset[0]['Stream'];
				$spent[$t]['max_id_row'] = $rowset[$rowCount - 1][0];
				$max_id_row = $spent[$t]['max_id_row'];
//Fn::debugToLog("spent t=", $t."	".$columnCount."	".$rowCount  . "	" . $rowset[0]['Stream']."		".  $rowset[$rowCount - 1][0]);
				//if ($max_id_row == 0) continue;
//конвертируем полученный rowset в удобный нам формат в array
				for ($dt = clone $dt1; $dt <= $dt2; $dt->modify('+1 month')) {
					$period = $dt->format('Y-m');
//Fn::debugToLog("", $t." ".$max_id_row." ".$period);
					$spent[$t][$max_id_row][$period]['SumSpent'] = 0;
				}
				for ($tr = 0; $tr < $max_id_row; $tr++) {
					for ($dt = clone $dt1; $dt <= $dt2; $dt->modify('+1 month')) {
						$period = $dt->format('Y-m');
						for ($row = 1; $row < $rowCount; $row++) {
							if ($rowset[$row][0] != $tr + 1 || $period != $rowset[$row]['Period']) continue;
							$spent[$t][$tr]['Field1']['Name'] = $rowset[$row]['Stream'];
							$spent[$t][$tr][$period]['Period'] = $rowset[$row]['Period'];
							$spent[$t][$tr][$period]['CatID'] = $rowset[$row]['CatID'];
							$spent[$t][$tr][$period]['SumSpent'] = $rowset[$row]['SumSpent'];
							$spent[$t][$max_id_row]['Field1']['Name'] = 'Итого:';
							$spent[$t][$max_id_row][$period]['SumSpent'] += $rowset[$row]['SumSpent'];
						}
					}
				}
//Fn::debugToLog("spent".$t, json_encode($spent[$t]));
			} $t++;
		} while ($stmt->nextRowset());
//запишем суммы затрат в таблицу валовый доход		
		$count_t = $t;
//		$count_spent = ($count_t < 5 ? $count_t : 5);
		$count_spent = ($count_t < $cnt_tbl ? $count_t : $cnt_tbl);
		for ($t = 0; $t < $count_spent; $t++) {
			for ($dt = clone $dt1; $dt <= $dt2; $dt->modify('+1 month')) {
				$period = $dt->format('Y-m');
//				Fn::debugToLog("итоги", $t.'   '.$period);
//				Fn::debugToLog("итоги", Fn::nfPendel($spent[$t][$spent[$t]['max_id_row']][$period]['SumSpent']));
				$dataGross[$t][$period]['SumSpent'] = $spent[$t][$spent[$t]['max_id_row']][$period]['SumSpent'];
			}
		}
//Fn::objectToLog($spent);
//Fn::objectToLog($dataGross);

		$str .= '<table id="table1" class="table table-striped table-bordered" cellspacing="0"  width="100%">';
//формируем шапку Валовый доход
			$str .= '<thead><tr>
				<th rowspan=2>№ п-п</th>
				<th rowspan=2>Валовый доход</th>';
			for ($dt = clone $dt1; $dt <= $dt2; $dt->modify('+1 month')) {
				$str .= '<th colspan=4>' . $dt->format('Y-m') . '</th>';
			}
			$str .= '</tr><tr>';
			for ($dt = clone $dt1; $dt <= $dt2; $dt->modify('+1 month')) {
				$str .= '<th>Оборот</th>
				<th>Себест.</th>
				<th>Маржа</th>
				<th>Прибыль</th>
			   ';
			}
			$str .= '</tr></thead>';
//формируем таблицу
			$str .= '<tbody>';
			for ($tr = 0; $tr < $dg_max_id_row; $tr++) {
				$str .= '<tr>';
				$str .= '<td>' . ($tr + 1) . '</td>';
				$str .= '<td class="TAL">' . $dataGross[$tr]['Field1']['Name'] . '</td>';
				for ($dt = clone $dt1; $dt <= $dt2; $dt->modify('+1 month')) {
					$period = $dt->format('Y-m');
					$str .= '<td class="TAR"><a href="javascript:history(\'../reports_fin/pendel_dop?'
							. 'catid=' . $dataGross[$tr][$period]['CatID']
							. '&partner_period=' . $dataGross[$tr][$period]['Period']
							. '\');">'
							. Fn::nfPendel($dataGross[$tr][$period]['Oborot'])
							. '</a></td>';
					$str .= '<td class="TAR"><a href="javascript:history(\'../reports_fin/pendel_dop?'
							. 'catid=' . $dataGross[$tr][$period]['CatID']
							. '&partner_period=' . $dataGross[$tr][$period]['Period']
							. '\');">'
							. Fn::nfPendel($dataGross[$tr][$period]['Sebest'])
							. '</a></td>';
					$str .= '<td class="TAR">'.Fn::nfPendelP($dataGross[$tr][$period]['Percent']).'</td>';
					$str .= '<td class="TAR"><a href="javascript:history(\'../reports_fin/pendel_dop?'
							. 'catid=' . $dataGross[$tr][$period]['CatID']
							. '&partner_period=' . $dataGross[$tr][$period]['Period']
							. '\');">'
							. Fn::nfPendel($dataGross[$tr][$period]['Dohod'])
							. '</a></td>';
				}
				$str .= '</tr>';
			}
			$str .= "</tbody>";
//формируем итоги
			$str .= '<thead>';
			$str .= '<tr>';
			$str .= '<th></th>';
			$str .= '<th class="TAL">' . $dataGross[$tr]['Field1']['Name'] . '</th>';
			for ($dt = clone $dt1; $dt <= $dt2; $dt->modify('+1 month')) {
				$period = $dt->format('Y-m');
				$percent = 0;
				if($dataGross[$tr][$period]['Oborot']<>0){
					$percent = round(100 * (1-$dataGross[$tr][$period]['Sebest']/$dataGross[$tr][$period]['Oborot']),2);
				}
				$str .= '<th class="TAR">' . Fn::nfPendel($dataGross[$tr][$period]['Oborot']) . '</th>';
				$str .= '<th class="TAR">' . Fn::nfPendel($dataGross[$tr][$period]['Sebest']) . '</th>';
				$str .= '<th class="TAR">' . Fn::nfPendelP($percent).'</th>';
				$str .= '<th class="TAR">' . Fn::nfPendel($dataGross[$tr][$period]['Dohod']) . '</th>';
			}
			$str .= '</tr>';
			$str .= "</thead>";
//отступ
		$str .= '<thead><tr><th colspan=1000 class="h10"></th></tr></thead>';
//формируем шапку Валовая прибыль
		$str .= '<thead><tr>
				<th rowspan=2>№ п-п</th>
				<th rowspan=2>Валовая прибыль</th>';
		for ($dt = clone $dt1; $dt <= $dt2; $dt->modify('+1 month')) {
			$str .= '<th colspan=4>' . $dt->format('Y-m') . '</th>';
			$total[$period]['Dohod'] = 0;
			$total[$period]['SumSpent'] = 0;
			$total[$period]['Prib'] = 0;
		}
		$str .= '</tr><tr>';
		for ($dt = clone $dt1; $dt <= $dt2; $dt->modify('+1 month')) {
			$str .= '<th>Вал.прибыль</th>
					<th>Затраты</th>
					<th>% затрат</th>
					<th>Прибыль</th>
				   ';
		}
		$str .= '</tr></thead>';
//формируем таблицу Валовая прибыль
		$total = array();
		$str .= '<tbody>';
		for ($tr = 0; $tr < $dg_max_id_row; $tr++) {
			$str .= '<tr>';
			$str .= '<td>' . ($tr + 1) . '</td>';
			$str .= '<td class="TAL">' . $dataGross[$tr]['Field1']['Name'] . '</td>';
			for ($dt = clone $dt1; $dt <= $dt2; $dt->modify('+1 month')) {
				$period = $dt->format('Y-m');
				$dohod = $dataGross[$tr][$period]['Dohod'];
				$sumspent = $dataGross[$tr][$period]['SumSpent'];
				$total[$period]['Dohod'] += $dohod;
				$total[$period]['SumSpent'] += $sumspent;
				$total[$period]['Prib'] += $dohod;
				$total[$period]['Prib'] -= $sumspent;
				$percent = 0;
				if($dohod<>0) $percent = round(100 * $sumspent / $dohod,2);
				$str .= '<td class="TAR">'.Fn::nfPendel($dohod).'</a></td>';
				$str .= '<td class="TAR">'.Fn::nfPendel($sumspent).'</a></td>';
				$str .= '<td class="TAR">'.Fn::nfPendelP($percent).'</a></td>';
				$str .= '<td class="TAR">'.Fn::nfPendel($dohod - $sumspent).'</a></td>';
			}
			$str .= '</tr>';
		}
		$str .= "</tbody>";
//формируем итоги Валовая прибыль
		$str .= '<thead>';
		$str .= '<tr>';
		$str .= '<th></th>';
		$str .= '<th class="TAL">' . $dataGross[$tr]['Field1']['Name'] . '</th>';
		for ($dt = clone $dt1; $dt <= $dt2; $dt->modify('+1 month')) {
			$period = $dt->format('Y-m');
			$percent = 0;
			if ($total[$period]['Dohod'] <> 0) $percent = round(100 * $total[$period]['SumSpent'] / $total[$period]['Dohod'], 2);
			$str .= '<th class="TAR">' . Fn::nfPendel($total[$period]['Dohod']) . '</th>';
			$str .= '<th class="TAR">' . Fn::nfPendel($total[$period]['SumSpent']) . '</th>';
			$str .= '<th class="TAR">' . Fn::nfPendelP($percent) . '</th>';
			$str .= '<th class="TAR">' . Fn::nfPendel($total[$period]['Prib']) . '</th>';
		}
		$str .= '</tr>';
		$str .= "</thead>";

//отступ
		$str .= '<thead><tr><th colspan=1000 class="h10"></th></tr></thead>';
//		$str .= '</table><table id="example" class="table table-striped table-bordered" cellspacing="0"  width="100%">';
//формируем шапку ЗАТРАТЫ Переменные расходы
			$str .= '<thead><tr><th></th>
					<th>Переменные расходы</th>';
			for ($dt = clone $dt1; $dt <= $dt2; $dt->modify('+1 month')) {
				$period = $dt->format('Y-m');
				$total[$period]['SumSpent'] = 0;
				$str .= '<th colspan=4></th>';
			}
			$str .= '</tr>';
			$str .= '</thead>';
		
		for($t = 0; $t < $count_t; $t++){
			$max_id_row = $spent[$t]['max_id_row'];
//отступ
			if(!strpos($spent[$t]['name'],'EBITDA')){
				$str .= '<thead><tr><th colspan=1000 class="h10"></th></tr></thead>';
			}
//формируем шапку для таблиц затрат (их может быть много)
			$str .= '<thead><tr><th></th>
					<th>'.$spent[$t]['name'].'</th>';
			for ($dt = clone $dt1; $dt <= $dt2; $dt->modify('+1 month')) {
				$period = $dt->format('Y-m');
				if(!strpos($spent[$t]['name'],'EBITDA')){
					$str .= '<th colspan=4></th>';
				}else{
//					Fn::debugToLog("$period", $total[$period]['Prib'].'	'.$total[$period]['SumSpent']);
					$str .= '<th colspan=4>'.Fn::nfPendel($total[$period]['Prib'] - $total[$period]['SumSpent']).'</th>';
				}
			}
			$str .= '</tr>';
			$str .= '</thead>';
//формируем таблицу 3
			$str .= '<tbody>';
			for ($tr = 0; $tr < $max_id_row; $tr++) {
				$str .= '<tr>';
				$str .= '<td>' . ($tr + 1) . '</td>';
				$str .= '<td class="TAL">' . $spent[$t][$tr]['Field1']['Name'] . '</td>';
				for ($dt = clone $dt1; $dt <= $dt2; $dt->modify('+1 month')) {
					$period = $dt->format('Y-m');
					$str .= '<td colspan=4>'
							. '<a href="javascript:history(\'../reports_fin/pendel_dop?'
							. 'catid=' . $spent[$t][$tr][$period]['CatID']
							. '&spent_period=' . $spent[$t][$tr][$period]['Period']
							. '\');">'
							. Fn::nfPendel($spent[$t][$tr][$period]['SumSpent'])
							. '</a>'
							. '</td>';
				}
				$str .= '</tr>';
			}
			$str .= "</tbody>";
//формируем итоги 3
			$str .= '<thead>';
			$str .= '<tr>';
			$str .= '<th></th>';
			$str .= '<th class="TAL">' . $spent[$t][$tr]['Field1']['Name'] . '</th>';
			for ($dt = clone $dt1; $dt <= $dt2; $dt->modify('+1 month')) {
				$period = $dt->format('Y-m');
				if($t >= $cnt_tbl){ 
//					Fn::debugToLog("вошло:	$t	$tr	$period	SumSpent=", $spent[$t][$tr][$period]['SumSpent']);
					$total[$period]['SumSpent'] += $spent[$t][$tr][$period]['SumSpent'];
				}
				$str .= '<th colspan=4>' . Fn::nfPendel($spent[$t][$tr][$period]['SumSpent']) . '</th>';
			}
			$str .= '</tr>';
			$str .= "</thead>";
		}
//отступ
		if (!strpos($spent[$t]['name'], 'EBITDA'))
			$str .= '<thead><tr><th colspan=1000 class="h10"></th></tr></thead>';
//формируем конечный итог
		$str .= '<thead>';
		$str .= '<tr>';
		$str .= '<th></th>';
		$str .= '<th class="TAL">ИТОГО ЧИСТАЯ ПРИБЫЛЬ:</th>';
		for ($dt = clone $dt1; $dt <= $dt2; $dt->modify('+1 month')) {
			$period = $dt->format('Y-m');
			//$total[$period]['SumSpent'] -= $spent[$t][$tr][$period]['SumSpent'];
			$str .= '<th colspan=4>' . Fn::nfPendel($total[$period]['Prib']-$total[$period]['SumSpent']) . '</th>';
		}
		$str .= '</tr>';
		$str .= "</thead>";
		$str .= "</table>";
		$response->table1 = $str;
		header("Content-type: application/json;charset=utf8");
		echo json_encode($response);
	}

//report setting
	public function set_report_setting() {
		foreach ($_REQUEST as $arg => $val) ${$arg} = $val;
//Fn::debugToLog('QUERY_STRING', urldecode($_SERVER['QUERY_STRING']));
		$stmt = $this->db->prepare("CALL pr_report_setting('set', @id, ?, ?, ?, ?)");
		$stmt->bindParam(1, $_SESSION['UserID'], PDO::PARAM_STR);
		$stmt->bindParam(2, $sid, PDO::PARAM_STR);
		$stmt->bindParam(3, $sname, PDO::PARAM_STR);
		$stmt->bindParam(4, urldecode($_SERVER['QUERY_STRING']), PDO::PARAM_STR);
// вызов хранимой процедуры
		$stmt->execute();
		if (!Fn::checkErrorMySQLstmt($stmt))
			return false;
		$result = false;
		do {
			$rowset = $stmt->fetchAll(PDO::FETCH_BOTH);
			if ($rowset) {
				foreach ($rowset as $row) {
					$result = $row[0];
				}
			}
		} while ($stmt->nextRowset());
		echo $result;
	}
	public function get_report_setting_list() {
		foreach ($_REQUEST as $arg => $val)
			${$arg} = $val;
//Fn::debugToLog('QUERY_STRING', urldecode($_SERVER['QUERY_STRING']));
		$stmt = $this->db->prepare("CALL pr_report_setting('get list', @id, ?, ?, ?, ?)");
		$stmt->bindParam(1, $_SESSION['UserID'], PDO::PARAM_STR);
		$stmt->bindParam(2, $sid, PDO::PARAM_STR);
		$stmt->bindParam(3, $sname, PDO::PARAM_STR);
		$stmt->bindParam(4, $url, PDO::PARAM_STR);
// вызов хранимой процедуры
		$stmt->execute();
		if (!Fn::checkErrorMySQLstmt($stmt))
			return false;
		$result = false;
		//$response = new stdClass();
		do {
			$rowset = $stmt->fetchAll(PDO::FETCH_BOTH);
			if ($rowset) {
				$i = 0;
				foreach ($rowset as $row) {
					$response[$i] = array('id' => $row['SettingName'], 
										  'text' => $row['SettingName']
					);
					$i++;
				}
			}
		} while ($stmt->nextRowset());
		header("Content-type: application/json;charset=utf-8");
		echo json_encode($response);
		return;
	}
	public function get_report_setting_byName() {
		foreach ($_REQUEST as $arg => $val)
			${$arg} = $val;
//Fn::debugToLog('QUERY_STRING', urldecode($_SERVER['QUERY_STRING']));
//		$url = urldecode($_SERVER['QUERY_STRING']);
		$stmt = $this->db->prepare("CALL pr_report_setting('get by name', @id, ?, ?, ?, ?)");
		$stmt->bindParam(1, $_SESSION['UserID'], PDO::PARAM_STR);
		$stmt->bindParam(2, $sid, PDO::PARAM_STR);
		$stmt->bindParam(3, $sname, PDO::PARAM_STR);
		$stmt->bindParam(4, $url, PDO::PARAM_STR);
// вызов хранимой процедуры
		$stmt->execute();
		if (!Fn::checkErrorMySQLstmt($stmt))
			return false;
		$result = false;
		//$response = new stdClass();
		do {
			$rowset = $stmt->fetchAll(PDO::FETCH_BOTH);
			if ($rowset) {
				foreach ($rowset as $row) {
					//Fn::debugToLog("get by name", explode("&", $row['Setting']));
					$response->Setting = $row['Setting'];
				}
			}
		} while ($stmt->nextRowset());
		header("Content-type: application/json;charset=utf-8");
		echo json_encode($response);
	}

//project
	public function project_info() {
		foreach ($_REQUEST as $arg => $val)
			${$arg} = $val;
//Fn::debugToLog('QUERY_STRING', urldecode($_SERVER['QUERY_STRING']));
		$stmt = $this->db->prepare("CALL pr_project('info', @id, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
		$stmt->bindParam(1, $projectid, PDO::PARAM_STR);
		$stmt->bindParam(2, $departmentID, PDO::PARAM_STR);
		$stmt->bindParam(3, $name, PDO::PARAM_STR);
		$stmt->bindParam(4, $description, PDO::PARAM_STR);
		$stmt->bindParam(5, $status, PDO::PARAM_STR);
		$stmt->bindParam(6, $userID_resp, PDO::PARAM_STR);
		$stmt->bindParam(7, $userID_create, PDO::PARAM_STR);
		$stmt->bindParam(8, $DT_plan, PDO::PARAM_STR);
		$stmt->bindParam(9, $DT_fact, PDO::PARAM_STR);
// вызов хранимой процедуры
		$stmt->execute();
		if (!Fn::checkErrorMySQLstmt($stmt))
			return false;
		$rowset = $stmt->fetchAll(PDO::FETCH_BOTH);
		foreach ($rowset as $row) {
			break; //берем первую запись из результата
		}
		return $row;
	}
	public function project_save() {
		foreach ($_REQUEST as $arg => $val)
			${$arg} = $val;
//Fn::paramToLog();
		if ($projectid == '')	$projectid = null;
		if ($unitID == '')		$unitID = null;
		if ($status == '')		$status = 0;
		if ($userID_resp == '')	$userID_resp = null;
		if(isset($DT_plan) && $DT_plan!=''){
			$DT_plan = DateTime::createFromFormat('d?m?Y', $DT_plan);
			$DT_plan = $DT_plan->format('Ymd');
		}
		if(isset($DT_fact) && $DT_fact!=''){
			$DT_fact = DateTime::createFromFormat('d?m?Y', $DT_fact);
			$DT_fact = $DT_fact->format('Ymd');
		}
//Fn::paramToLog();
		$stmt = $this->db->prepare("CALL pr_project('save', @id, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
		$stmt->bindParam(1, $projectid, PDO::PARAM_STR);
		$stmt->bindParam(2, $unitID, PDO::PARAM_STR);
		$stmt->bindParam(3, $name, PDO::PARAM_STR);
		$stmt->bindParam(4, $description, PDO::PARAM_STR);
		$stmt->bindParam(5, $status, PDO::PARAM_STR);
		$stmt->bindParam(6, $userID_resp, PDO::PARAM_STR);
		$stmt->bindParam(7, $_SESSION['UserID'], PDO::PARAM_STR);
		$stmt->bindParam(8, $DT_plan, PDO::PARAM_STR);
		$stmt->bindParam(9, $DT_fact, PDO::PARAM_STR);
// вызов хранимой процедуры
		$stmt->execute();
		$this->echo_response($stmt);
	}

//goods
	public function good_info() {
		foreach ($_REQUEST as $arg => $val) ${$arg} = $val;
//Fn::debugToLog('QUERY_STRING', urldecode($_SERVER['QUERY_STRING']));
//CALL pr_goods(action, _GoodID, _Good1C, _Article, _Name, _Division, _Unit_in_pack, _Unit, _Weight, _DiscountMax, _FreeBalance, _PriceBase, _Price1, _Price2, _Price3, _Price4, _id);
		$stmt = $this->db->prepare("CALL pr_goods_site('info', @_id, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
		$stmt->bindParam(1, $goodid, PDO::PARAM_STR);
		$stmt->bindParam(2, $article, PDO::PARAM_STR);
		$stmt->bindParam(3, $name, PDO::PARAM_STR);
		$stmt->bindParam(4, $namestickers, PDO::PARAM_STR);
		$stmt->bindParam(5, $unit, PDO::PARAM_STR);
		$stmt->bindParam(6, $trademark, PDO::PARAM_STR);
		$stmt->bindParam(7, $countryproducer, PDO::PARAM_STR);
		$stmt->bindParam(8, $typesticker, PDO::PARAM_STR);
		$stmt->bindParam(9, $packtype, PDO::PARAM_STR);
		$stmt->bindParam(10, $packmaterial, PDO::PARAM_STR);
		$stmt->bindParam(11, $foldorder, PDO::PARAM_STR);
		$stmt->bindParam(12, $segment, PDO::PARAM_STR);
		$stmt->bindParam(13, $visible, PDO::PARAM_STR);
		$stmt->bindParam(14, $service, PDO::PARAM_STR);
		$stmt->bindParam(15, $division, PDO::PARAM_STR);
		$stmt->bindParam(16, $length, PDO::PARAM_STR);
		$stmt->bindParam(17, $width, PDO::PARAM_STR);
		$stmt->bindParam(18, $height, PDO::PARAM_STR);
		$stmt->bindParam(19, $weight, PDO::PARAM_STR);
		$stmt->bindParam(20, $unit_in_pack, PDO::PARAM_STR);
		$stmt->bindParam(21, $perioddelivery, PDO::PARAM_STR); 
		$stmt->bindParam(22, $discountmax, PDO::PARAM_STR); 
		$stmt->bindParam(23, $visibleinorder, PDO::PARAM_STR);
// вызов хранимой процедуры
		$stmt->execute();
		if (!Fn::checkErrorMySQLstmt($stmt))
			return false;
		$rowset = $stmt->fetchAll(PDO::FETCH_BOTH);
		foreach ($rowset as $row) {
			break; //берем первую запись из результата
		}
//Fn::debugToLog("good info", json_encode($row));
		return $row;
	}
	public function good_save() {
		foreach ($_REQUEST as $arg => $val){
			${$arg} = $val;
			if($val=='') ${$arg} = null;
		}
//Fn::paramToLog();
//		if ($visible == 1)	{
//			$visible = true;
//		} else {
//			$visible = false;
//		}
		$stmt = $this->db->prepare("CALL pr_goods_site('save', @_id, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
		$stmt->bindParam(1, $goodid, PDO::PARAM_STR);
		$stmt->bindParam(2, $article, PDO::PARAM_STR);
		$stmt->bindParam(3, $name, PDO::PARAM_STR);
		$stmt->bindParam(4, $namestickers, PDO::PARAM_STR);
		$stmt->bindParam(5, $unit, PDO::PARAM_STR);
		$stmt->bindParam(6, $trademark, PDO::PARAM_STR);
		$stmt->bindParam(7, $countryproducer, PDO::PARAM_STR);
		$stmt->bindParam(8, $typesticker, PDO::PARAM_STR);
		$stmt->bindParam(9, $packtype, PDO::PARAM_STR);
		$stmt->bindParam(10, $packmaterial, PDO::PARAM_STR);
		$stmt->bindParam(11, $foldorder, PDO::PARAM_STR);
		$stmt->bindParam(12, $segment, PDO::PARAM_INT);
		$stmt->bindParam(13, $visible, PDO::PARAM_STR);
		$stmt->bindParam(14, $service, PDO::PARAM_STR);
		$stmt->bindParam(15, $division, PDO::PARAM_STR);
		$stmt->bindParam(16, $length, PDO::PARAM_STR);
		$stmt->bindParam(17, $width, PDO::PARAM_STR);
		$stmt->bindParam(18, $height, PDO::PARAM_STR);
		$stmt->bindParam(19, $weight, PDO::PARAM_STR);
		$stmt->bindParam(20, $unit_in_pack, PDO::PARAM_STR);
		$stmt->bindParam(21, $perioddelivery, PDO::PARAM_STR);
		$stmt->bindParam(22, $discountmax, PDO::PARAM_STR);
		$stmt->bindParam(23, $visibleinorder, PDO::PARAM_STR);
// вызов хранимой процедуры
		$stmt->execute();
		$this->echo_response($stmt);
	}
	public function good_param_save(){
		foreach ($_REQUEST as $arg => $val) {
			${$arg} = $val;
			if ($val == '')	${$arg} = null;
		}
Fn::paramToLog();

		$stmt = $this->db->prepare("CALL pr_goods_param( ?, ?, ?)");
		$stmt->bindParam(1, $action, PDO::PARAM_STR);
		$stmt->bindParam(2, $goodid, PDO::PARAM_STR);
		$stmt->bindParam(3, $value, PDO::PARAM_STR);
// вызов хранимой процедуры
		$stmt->execute();
//		$this->echo_response($stmt);

		$response = new stdClass();
		$response->success	= Fn::checkErrorMySQLstmt($stmt);
		$response->goodid	= $goodid;
		$response->article	= '';
		$response->name		= '';
		$response->value_old= '';
		$response->value_new= '';

		$rowset = $stmt->fetchAll(PDO::FETCH_BOTH);
		foreach ($rowset as $row) {
			$response->success = $row[0];
			$response->goodid = $row[1];
			$response->article = $row[2];
			$response->name = $row[3];
			$response->value_old = $row[4];
			$response->value_new = $row[5];
		}
//Fn::debugToLog("set param", json_encode($response));
		header("Content-type: application/json;charset=utf-8");
		echo json_encode($response);
	}
//balance_min
	public function balance_min_set(){
		foreach ($_REQUEST as $arg => $val) {
			${$arg} = $val;
			if ($val == '')	${$arg} = null;
		}
		$goodid = $id;
//Fn::paramToLog();
		$stmt = $this->db->prepare("CALL pr_balance_min( ?, @id, ?, ?, ?, ?, ?, ?, ?, ?)");
		$stmt->bindParam(1, $action, PDO::PARAM_STR);
		$stmt->bindParam(2, $clientid, PDO::PARAM_STR);
		$stmt->bindParam(3, $goodid, PDO::PARAM_STR);
		$stmt->bindParam(4, $BalanceMinM, PDO::PARAM_STR);
		$stmt->bindParam(5, $_AVG, PDO::PARAM_STR);
		$stmt->bindParam(6, $_STD, PDO::PARAM_STR);
		$stmt->bindParam(7, $_DT_start, PDO::PARAM_STR);
		$stmt->bindParam(8, $_DT_stop, PDO::PARAM_STR);
		$stmt->bindParam(9, $_param, PDO::PARAM_STR);
// вызов хранимой процедуры
		$stmt->execute();
//		$this->echo_response($stmt);

		$response = new stdClass();
		$response->success	= Fn::checkErrorMySQLstmt($stmt);
		$response->goodid	= $goodid;
		$response->value_old = '';
		$response->value_new = '';

		$rowset = $stmt->fetchAll(PDO::FETCH_BOTH);
		foreach ($rowset as $row) {
			$response->success = $row[0];
			$response->goodid = $row[1];
			$response->value_old = $row[2];
			$response->value_new = $row[3];
		}
//Fn::debugToLog("set param", json_encode($response));
		header("Content-type: application/json;charset=utf-8");
		echo json_encode($response);
	}
	public function balance_min_set_auto(){
		foreach ($_REQUEST as $arg => $val) {
			${$arg} = $val;
			if ($val == '')	${$arg} = null;
		}
		$goodid = $id;
		$url = urldecode($_SERVER['QUERY_STRING']);
//Fn::paramToLog();
		$stmt = $this->db->prepare("CALL pr_balance_min( ?, @id, ?, ?, ?, ?, ?, ?, ?, ?)");
		$stmt->bindParam(1, $action, PDO::PARAM_STR);
		$stmt->bindParam(2, $point_balance_min, PDO::PARAM_STR);
		$stmt->bindParam(3, $_goodid, PDO::PARAM_STR);
		$stmt->bindParam(4, $_BalanceMinM, PDO::PARAM_STR);
		$stmt->bindParam(5, $_AVG, PDO::PARAM_STR);
		$stmt->bindParam(6, $_STD, PDO::PARAM_STR);
		$stmt->bindParam(7, $_DT_start, PDO::PARAM_STR);
		$stmt->bindParam(8, $_DT_stop, PDO::PARAM_STR);
		$stmt->bindParam(9, $url, PDO::PARAM_STR);
// вызов хранимой процедуры
		$stmt->execute();
//		$this->echo_response($stmt);

		$response = new stdClass();
		$response->success	= Fn::checkErrorMySQLstmt($stmt);
		$response->goodid	= $goodid;
		$response->value_old = '';
		$response->value_new = '';
		$response->clientid = $point_balance_min;

		$rowset = $stmt->fetchAll(PDO::FETCH_BOTH);
		foreach ($rowset as $row) {
			$response->success = $row[0];
			$response->goodid = $row[1];
			$response->value_old = $row[2];
			$response->value_new = $row[3];
		}
//Fn::debugToLog("set param", json_encode($response));
		header("Content-type: application/json;charset=utf-8");
		echo json_encode($response);
	}

//point
	public function point_info() {
		foreach ($_REQUEST as $arg => $val)
			${$arg} = $val;
//Fn::debugToLog('QUERY_STRING', urldecode($_SERVER['QUERY_STRING']));
		$stmt = $this->db->prepare("CALL pr_point('info', @id, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
		$stmt->bindParam(1, $clientID, PDO::PARAM_STR);
		$stmt->bindParam(2, $matrixID, PDO::PARAM_STR);
		$stmt->bindParam(3, $nameShort, PDO::PARAM_STR);
		$stmt->bindParam(4, $nameValid, PDO::PARAM_STR);
		$stmt->bindParam(5, $city, PDO::PARAM_STR);
		$stmt->bindParam(6, $address, PDO::PARAM_STR);
		$stmt->bindParam(7, $telephone, PDO::PARAM_STR);
		$stmt->bindParam(8, $countTerminal, PDO::PARAM_STR);
		$stmt->bindParam(9, $rD, PDO::PARAM_STR);
		$stmt->bindParam(10, $priceType, PDO::PARAM_STR);
		$stmt->bindParam(11, $appVersion, PDO::PARAM_STR);
		$stmt->bindParam(12, $statusID, PDO::PARAM_STR);
		$stmt->bindParam(13, $balanceActivity, PDO::PARAM_STR);
		$stmt->bindParam(14, $x1C, PDO::PARAM_STR);
		$stmt->bindParam(15, $label, PDO::PARAM_STR);
// вызов хранимой процедуры
		$stmt->execute();
		if (!Fn::checkErrorMySQLstmt($stmt))
			return false;
		$rowset = $stmt->fetchAll(PDO::FETCH_BOTH);
		foreach ($rowset as $row) {
			break; //берем первую запись из результата
		}
		return $row;
	}
	public function point_save() {
		foreach ($_REQUEST as $arg => $val)
			${$arg} = $val;
		if ($matrixID == '') $matrixID = null;
		if ($version == '')	$version = null;
		if ($priceType == '') $priceType = null;
		if ($balanceActivity == '') $balanceActivity = null;
//Fn::paramToLog();
		$stmt = $this->db->prepare("CALL pr_point('save', @id, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
		$stmt->bindParam(1, $clientID, PDO::PARAM_STR);
		$stmt->bindParam(2, $matrixID, PDO::PARAM_STR);
		$stmt->bindParam(3, $nameShort, PDO::PARAM_STR);
		$stmt->bindParam(4, $nameValid, PDO::PARAM_STR);
		$stmt->bindParam(5, $city, PDO::PARAM_STR);
		$stmt->bindParam(6, $address, PDO::PARAM_STR);
		$stmt->bindParam(7, $telephone, PDO::PARAM_STR);
		$stmt->bindParam(8, $countTerminal, PDO::PARAM_STR);
		$stmt->bindParam(9, $rD, PDO::PARAM_STR);
		$stmt->bindParam(10, $priceType, PDO::PARAM_STR);
		$stmt->bindParam(11, $appVersion, PDO::PARAM_STR);
		$stmt->bindParam(12, $version, PDO::PARAM_STR);
		$stmt->bindParam(13, $balanceActivity, PDO::PARAM_STR);
		$stmt->bindParam(14, $x1C, PDO::PARAM_STR);
		$stmt->bindParam(15, $label, PDO::PARAM_STR);
// вызов хранимой процедуры
		$stmt->execute();
		$this->echo_response($stmt);
	}

//task
	public function task_info() {
		foreach ($_REQUEST as $arg => $val)
			${$arg} = $val;
//Fn::debugToLog('QUERY_STRING', urldecode($_SERVER['QUERY_STRING']));
		$stmt = $this->db->prepare("CALL pr_project_content('info', @id, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
		$stmt->bindParam(1, $taskid, PDO::PARAM_STR);
		$stmt->bindParam(2, $projectid, PDO::PARAM_STR);
		$stmt->bindParam(3, $parentID, PDO::PARAM_STR);
		$stmt->bindParam(4, $status, PDO::PARAM_STR);
		$stmt->bindParam(5, $unitID, PDO::PARAM_STR);
		$stmt->bindParam(6, $name, PDO::PARAM_STR);
		$stmt->bindParam(7, $description, PDO::PARAM_STR);
		$stmt->bindParam(8, $userID_resp, PDO::PARAM_STR);
		$stmt->bindParam(9, $DT_plan, PDO::PARAM_STR);
		$stmt->bindParam(10, $DT_fact, PDO::PARAM_STR);
		$stmt->bindParam(11, $userID_create, PDO::PARAM_STR);
		$stmt->bindParam(12, $DT_create, PDO::PARAM_STR);
		$stmt->bindParam(13, $orderID, PDO::PARAM_STR);
// вызов хранимой процедуры
		$stmt->execute();
		if (!Fn::checkErrorMySQLstmt($stmt))
			return false;
		$rowset = $stmt->fetchAll(PDO::FETCH_BOTH);
		foreach ($rowset as $row) {
			break; //берем первую запись из результата
		}
		return $row;
	}
	public function task_save() {
		foreach ($_REQUEST as $arg => $val)
			${$arg} = $val;
		if ($taskid == '') $taskid = null;
		if ($projectid == '') $projectid = null;
		if ($parentID == '') $parentID = null;
		if ($status == '') $status = 0;
		if ($unitID == '') $unitID = null;
		if ($userID_resp == '')	$userID_resp = null;
		if ($orderID == '')	$orderID = null;
		if (isset($DT_plan) && $DT_plan != '') {
			$DT_plan = DateTime::createFromFormat('d?m?Y', $DT_plan);
			$DT_plan = $DT_plan->format('Ymd');
		}
		if (isset($DT_fact) && $DT_fact != '') {
			$DT_fact = DateTime::createFromFormat('d?m?Y', $DT_fact);
			$DT_fact = $DT_fact->format('Ymd');
		}
//Fn::paramToLog();
//Fn::debugToLog('QUERY_STRING', urldecode($_SERVER['QUERY_STRING']));
		$stmt = $this->db->prepare("CALL pr_project_content('save', @id, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
		$stmt->bindParam(1, $taskid, PDO::PARAM_STR);
		$stmt->bindParam(2, $projectid, PDO::PARAM_STR);
		$stmt->bindParam(3, $parentID, PDO::PARAM_STR);
		$stmt->bindParam(4, $status, PDO::PARAM_STR);
		$stmt->bindParam(5, $unitID, PDO::PARAM_STR);
		$stmt->bindParam(6, $name, PDO::PARAM_STR);
		$stmt->bindParam(7, $description, PDO::PARAM_STR);
		$stmt->bindParam(8, $userID_resp, PDO::PARAM_STR);
		$stmt->bindParam(9, $DT_plan, PDO::PARAM_STR);
		$stmt->bindParam(10, $DT_fact, PDO::PARAM_STR);
		$stmt->bindParam(11, $_SESSION['UserID'], PDO::PARAM_STR);
		$stmt->bindParam(12, $DT_create, PDO::PARAM_STR);
		$stmt->bindParam(13, $orderID, PDO::PARAM_STR);
// вызов хранимой процедуры
		$stmt->execute();
		$this->echo_response($stmt);
	}

//discount cards 
	public function discountcard_info() {
		foreach ($_REQUEST as $arg => $val)
			${$arg} = $val;
//Fn::paramToLog();
//Fn::debugToLog('QUERY_STRING', urldecode($_SERVER['QUERY_STRING']));
		$stmt = $this->db->prepare("CALL pr_discountCard('info', @id, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
		$stmt->bindParam(1, $cardid, PDO::PARAM_STR);
		$stmt->bindParam(2, $name, PDO::PARAM_STR);
		$stmt->bindParam(3, $dateOfIssue, PDO::PARAM_STR);
		$stmt->bindParam(4, $dateOfCancellation, PDO::PARAM_STR);
		$stmt->bindParam(5, $clientID, PDO::PARAM_STR);
		$stmt->bindParam(6, $address, PDO::PARAM_STR);
		$stmt->bindParam(7, $eMail, PDO::PARAM_STR);
		$stmt->bindParam(8, $phone, PDO::PARAM_STR);
		$stmt->bindParam(9, $animal, PDO::PARAM_STR);
		$stmt->bindParam(10, $startPercent, PDO::PARAM_STR);
		$stmt->bindParam(11, $startSum, PDO::PARAM_STR);
		$stmt->bindParam(12, $dopSum, PDO::PARAM_STR);
		$stmt->bindParam(13, $percentOfDiscount, PDO::PARAM_STR);
		$stmt->bindParam(14, $howWeLearn, PDO::PARAM_STR);
		$stmt->bindParam(15, $notes, PDO::PARAM_STR);
// вызов хранимой процедуры
		$stmt->execute();
		if (!Fn::checkErrorMySQLstmt($stmt))
			return false;
		$rowset = $stmt->fetchAll(PDO::FETCH_BOTH);
		foreach ($rowset as $row) {
			break; //берем первую запись из результата
		}
		return $row;
	}
	public function discoundcard_history() {
		foreach ($_REQUEST as $arg => $val)
			${$arg} = $val;
//Fn::paramToLog();
		if ($checkid == '')	$checkid = null;
		$action = "history_".$oper;
		$checkid = str_replace(",",".",$checkid);
//Fn::paramToLog();
//Fn::debugToLog('QUERY_STRING', urldecode($_SERVER['QUERY_STRING']));
		$stmt = $this->db->prepare("CALL pr_discountCard(?, @id, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
		$stmt->bindParam(1, $action, PDO::PARAM_STR);
		$stmt->bindParam(2, $cardid, PDO::PARAM_STR);
		$stmt->bindParam(3, $name, PDO::PARAM_STR);
		$stmt->bindParam(4, $dateOfIssue, PDO::PARAM_STR);
		$stmt->bindParam(5, $dateOfCancellation, PDO::PARAM_STR);
		$stmt->bindParam(6, $clientID, PDO::PARAM_STR);
		$stmt->bindParam(7, $address, PDO::PARAM_STR);
		$stmt->bindParam(8, $eMail, PDO::PARAM_STR);
		$stmt->bindParam(9, $phone, PDO::PARAM_STR);
		$stmt->bindParam(10, $animal, PDO::PARAM_STR);
		$stmt->bindParam(11, $startPercent, PDO::PARAM_STR);
		$stmt->bindParam(12, $startSum, PDO::PARAM_STR);
		$stmt->bindParam(13, $checkid, PDO::PARAM_STR);
		$stmt->bindParam(14, $percentOfDiscount, PDO::PARAM_STR);
		$stmt->bindParam(15, $howWeLearn, PDO::PARAM_STR);
		$stmt->bindParam(16, $notes, PDO::PARAM_STR);
// вызов хранимой процедуры
		$stmt->execute();
		$this->echo_response($stmt);
	}
	public function discoundcard_save() {
		foreach ($_REQUEST as $arg => $val) ${$arg} = $val;
//Fn::paramToLog();
		if ($clientID == '') $clientID = null;
		if ($startPercent == '') $startPercent = 0;
		if ($startSum == '') $startSum = 0;
		if ($dopSum == '') $dopSum = 0;
		if ($percentOfDiscount == '') $percentOfDiscount = 0;
		if (isset($dateOfIssue) && $dateOfIssue != '') {
			if(strlen($dateOfIssue)<=10)$dateOfIssue .= "00:00:00";
			$dateOfIssue = DateTime::createFromFormat('d?m?Y H?i?s', $dateOfIssue);
			$dateOfIssue = $dateOfIssue->format('Ymd');
		}
		if (isset($dateOfCancellation) && $dateOfCancellation != '') {
			if (strlen($dateOfCancellation) <= 10)	$dateOfCancellation .= "00:00:00";
			$dateOfCancellation = DateTime::createFromFormat('d?m?Y H?i?s', $dateOfCancellation);
			$dateOfCancellation = $dateOfCancellation->format('Ymd');
		}
//Fn::paramToLog();
//Fn::debugToLog('QUERY_STRING', urldecode($_SERVER['QUERY_STRING']));
		$stmt = $this->db->prepare("CALL pr_discountCard('save', @id, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
		$stmt->bindParam(1, $cardid, PDO::PARAM_STR);
		$stmt->bindParam(2, $name, PDO::PARAM_STR);
		$stmt->bindParam(3, $dateOfIssue, PDO::PARAM_STR);
		$stmt->bindParam(4, $dateOfCancellation, PDO::PARAM_STR);
		$stmt->bindParam(5, $clientID, PDO::PARAM_STR);
		$stmt->bindParam(6, $address, PDO::PARAM_STR);
		$stmt->bindParam(7, $eMail, PDO::PARAM_STR);
		$stmt->bindParam(8, $phone, PDO::PARAM_STR);
		$stmt->bindParam(9, $animal, PDO::PARAM_STR);
		$stmt->bindParam(10, $startPercent, PDO::PARAM_STR);
		$stmt->bindParam(11, $startSum, PDO::PARAM_STR);
		$stmt->bindParam(12, $dopSum, PDO::PARAM_STR);
		$stmt->bindParam(13, $percentOfDiscount, PDO::PARAM_STR);
		$stmt->bindParam(14, $howWeLearn, PDO::PARAM_STR);
		$stmt->bindParam(15, $notes, PDO::PARAM_STR);
// вызов хранимой процедуры
		$stmt->execute();
		$this->echo_response($stmt);
	}

//sellers
	public function seller_info() {
		foreach ($_REQUEST as $arg => $val)
			${$arg} = $val;
//Fn::paramToLog();
//return;
//Fn::debugToLog('QUERY_STRING', urldecode($_SERVER['QUERY_STRING']));
		$stmt = $this->db->prepare("CALL pr_seller('info', @id, ?, ?, ?, ?, ?, ?, ?)");
		$stmt->bindParam(1, $sellerID, PDO::PARAM_STR);
		$stmt->bindParam(2, $kod1C, PDO::PARAM_STR);
		$stmt->bindParam(3, $clientID, PDO::PARAM_STR);
		$stmt->bindParam(4, $name, PDO::PARAM_STR);
		$stmt->bindParam(5, $post, PDO::PARAM_STR);
		$stmt->bindParam(6, $postID, PDO::PARAM_STR);
		$stmt->bindParam(7, $fired, PDO::PARAM_STR);
// вызов хранимой процедуры
		$stmt->execute();
		if (!Fn::checkErrorMySQLstmt($stmt))
			return false;
		$rowset = $stmt->fetchAll(PDO::FETCH_BOTH);
		foreach ($rowset as $row) {
			break; //берем первую запись из результата
		}
		return $row;
	}
	public function seller_save() {
		foreach ($_REQUEST as $arg => $val)
			${$arg} = $val;
//Fn::paramToLog();
//Fn::debugToLog('QUERY_STRING', urldecode($_SERVER['QUERY_STRING']));
		if ($sellerID == '') $sellerID = null;
		if ($sellerID == '-1') $sellerID = null;
		if ($postID == '')	$postID = null;
		if ($clientID == '') $clientID = 0;
		$stmt = $this->db->prepare("CALL pr_seller('save', @id, ?, ?, ?, ?, ?, ?, ?)");
		$stmt->bindParam(1, $sellerID, PDO::PARAM_STR);
		$stmt->bindParam(2, $kod1C, PDO::PARAM_STR);
		$stmt->bindParam(3, $clientID, PDO::PARAM_STR);
		$stmt->bindParam(4, $name, PDO::PARAM_STR);
		$stmt->bindParam(5, $post, PDO::PARAM_STR);
		$stmt->bindParam(6, $postID, PDO::PARAM_STR);
		$stmt->bindParam(7, $fired, PDO::PARAM_STR);
// вызов хранимой процедуры
		$stmt->execute();
		$this->echo_response($stmt);
	}

//users
	public function user_info($userVal) {
		foreach ($_REQUEST as $arg => $val)
			${$arg} = $val;
                if (isset($userVal)) $userID = $userVal;
//Fn::paramToLog();
//Fn::debugToLog('QUERY_STRING', urldecode($_SERVER['QUERY_STRING']));
		$stmt = $this->db->prepare("CALL pr_user_info('info', @id, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
		$stmt->bindParam(1, $userID, PDO::PARAM_STR);
		$stmt->bindParam(2, $login, PDO::PARAM_STR);
		$stmt->bindParam(3, $password, PDO::PARAM_STR);
		$stmt->bindParam(4, $email, PDO::PARAM_STR);
		$stmt->bindParam(5, $clientid, PDO::PARAM_STR);
		$stmt->bindParam(6, $userName, PDO::PARAM_STR);
		$stmt->bindParam(7, $userPhone, PDO::PARAM_STR);
		$stmt->bindParam(8, $accessLevel, PDO::PARAM_STR);
		$stmt->bindParam(9, $position, PDO::PARAM_STR);
// вызов хранимой процедуры
		$stmt->execute();
		if (!Fn::checkErrorMySQLstmt($stmt))
			return false;
		$rowset = $stmt->fetchAll(PDO::FETCH_BOTH);
		foreach ($rowset as $row) {
			break; //берем первую запись из результата
		}
//		Fn::debugToLog("row", json_encode($row));
		return $row;
	}
	public function user_save() {
		foreach ($_REQUEST as $arg => $val)
			${$arg} = $val;
//Fn::debugToLog('QUERY_STRING', urldecode($_SERVER['QUERY_STRING']));
		if ($userid == null)
			$userid = 0;
		if ($eMail == null)
			$eMail = '';
		if ($password == null)
			$password = '';
		if ($companyName == null)
			$companyName = '';
Fn::paramToLog();
		$stmt = $this->db->prepare("CALL pr_user_info('save', @id, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
		$stmt->bindParam(1, $userid, PDO::PARAM_STR);
		$stmt->bindParam(2, $login, PDO::PARAM_STR);
		$stmt->bindParam(3, $password, PDO::PARAM_STR);
		$stmt->bindParam(4, $eMail, PDO::PARAM_STR);
		$stmt->bindParam(5, $clientid, PDO::PARAM_STR);
		$stmt->bindParam(6, $userName, PDO::PARAM_STR);
		$stmt->bindParam(7, $userPhone, PDO::PARAM_STR);
		$stmt->bindParam(8, $accessLevel, PDO::PARAM_STR);
		$stmt->bindParam(9, $position, PDO::PARAM_STR);
// вызов хранимой процедуры
		$stmt->execute();
		$this->echo_response($stmt);
	}

//jqgrid
	public function get_jqgrid3() {
		foreach ($_REQUEST as $arg => $val)
			${$arg} = $val;
//Fn::debugToLog('QUERY_STRING', urldecode($_SERVER['QUERY_STRING']));
		$url = urldecode($_SERVER['QUERY_STRING']);
		$url = str_replace("field1", $f1, $url);
		$url = str_replace("field2", $f2, $url);
		$url = str_replace("field3", $f3, $url);
		$url = str_replace("field4", $f4, $url);
		$url = str_replace("field5", $f5, $url);
		$url = str_replace("field6", $f6, $url);
		$url = str_replace("field7", $f7, $url);
		$url = str_replace("field8", $f8, $url);
		$url = str_replace("field9", $f9, $url);
		$url = str_replace("field10", $f10, $url);
		$url = str_replace("field11", $f11, $url);
		$url = str_replace("field12", $f12, $url);
		$url = str_replace("field13", $f13, $url);
		$url = str_replace("field14", $f14, $url);
		$url = str_replace("field15", $f15, $url);

		$url = str_replace("pr.Status=-1", "pr.Status<>100", $url);
		$url = str_replace("pc.Status=-1", "pc.Status<>100", $url);

		$url = str_replace("==", "=", $url);
		$url = str_replace("=>", ">", $url);
		$url = str_replace("=<", "<", $url);
		$url = str_replace("=<>", "<>", $url);

//Fn::debugToLog('jqgrid3 action', $action);
Fn::debugToLog('jqgrid3 url', $url);
//Fn::paramToLog();

		$stmt = $this->db->prepare("CALL pr_jqgrid(?, @id, ?)");
		$stmt->bindParam(1, $action, PDO::PARAM_STR);
		$stmt->bindParam(2, $url, PDO::PARAM_STR);
	// вызов хранимой процедуры
		$stmt->execute();
		if (!Fn::checkErrorMySQLstmt($stmt))
			return false;
		$response = new stdClass();
		$response->records = 0;
		$response->page = 0;
		$response->total = 0;
		$r = 0;
		do {
			$rowset = $stmt->fetchAll(PDO::FETCH_BOTH);
			if ($rowset) {
				if ($r == 1) {
					$i = 0;
					foreach ($rowset as $row) {
						$response->records = $row['_rows_count'];
						$response->page = $row['_page'];
						$response->total = $row['_total_pages'];
						$i++;
					}
				} else {
					$i = 0;
					$colCount = $stmt->columnCount();
					foreach ($rowset as $row) {
						$response->rows[$i]['id'] = str_replace('.','_',$row[0]);
						$response->rows[$i]['cell'] = array($row[$f1],
							$row[$f2],
							$row[$f3],
							$row[$f4],
							$row[$f5],
							$row[$f6],
							$row[$f7],
							$row[$f8],
							$row[$f9],
							$row[$f10],
							$row[$f11],
							$row[$f12],
							$row[$f13],
							$row[$f14],
							$row[$f15],
						);
//						$response->rows[$i]['cell'] = array_values($row);
						//$a1 = array_fill_keys(array_keys($row),$row);
						//$a1 = array_fill(0,$colCount-1,$row);
						//$a1 = array_fill(0,1,array_values($row));
//						if (1 == 0) {
//							ob_start();
//							var_dump($response->rows[$i]['cell']);
//							var_dump(array_values($row));
//							//Fn::DebugToLog("test param\n" . $_SERVER['SCRIPT_FILENAME'] . "\n" . $_SERVER['REQUEST_URI'] . "\n", ob_get_clean());
//							Fn::DebugToLog("тест а1", ob_get_clean());
//							ob_end_clean();
//						}
						$i++;
					}
				}
			}
			$r++;
		} while ($stmt->nextRowset());
		
//Fn::DebugToLog("тест jqgrid3", json_encode($response));
		header("Content-type: application/json;charset=utf8");
		echo json_encode($response);
}

//for select2
	public function select2() {
		foreach ($_REQUEST as $arg => $val)
			${$arg} = $val;
//Fn::paramToLog();
//Fn::debugToLog('QUERY_STRING', urldecode($_SERVER['QUERY_STRING']));
		$stmt = $this->db->prepare("CALL pr_select2(?, @id, ?, ?)");
		$stmt->bindParam(1, $action, PDO::PARAM_STR);
		$stmt->bindParam(2, $name, PDO::PARAM_STR);
		$stmt->bindParam(3, $type, PDO::PARAM_STR);
// вызов хранимой процедуры
		$stmt->execute();
		if (!Fn::checkErrorMySQLstmt($stmt))
			return false;
		//$response = new stdClass();
		do {
			$rowset = $stmt->fetchAll(PDO::FETCH_BOTH);
			if ($rowset) {
				$i = 0;
				foreach ($rowset as $row) {
					$response[$i] = array('id' => $row[0], 'text' => $row[1]);
					$i++;
				}
			}
		} while ($stmt->nextRowset());
//Fn::debugToLog("", json_encode($response));
		header("Content-type: application/json;charset=utf-8");
		echo json_encode($response);
		return;
	}

//category
	public function get_tree_NS_category() {
		foreach ($_REQUEST as $arg => $val)
			${$arg} = $val;
		$result = Shop::GetCategoryTreeNS($this->dbi, $nodeid, $n_level, $n_left, $n_right);
		if ($nodeid > 0) {
			$n_level = $n_level + 1;
		} else {
			$n_level = 0;
		}
		$response->page = 1;
		$response->total = 1;
		$response->records = 1;
		$i = 0;
		while ($row = $result->fetch_array(MYSQLI_BOTH)) {
			if ($row['rgt'] == $row['lft'] + 1)
				$leaf = 'true';
			else
				$leaf = 'false';
			if ($n_level == $row['level']) { // we output only the needed level
				$response->rows[$i]['id'] = $row['CatID'];
				$response->rows[$i]['cell'] = array($row['CatID'],
					//$row['name'].' ('.$row['CatID'].')',
					$row['name'],
					$row['level'],
					$row['lft'],
					$row['rgt'],
					$leaf,
					'false'
				);
			}
			$i++;
		}
		header("Content-type: text/html;charset=utf-8");
		echo json_encode($response);
	}
	public function category_tree_oper() {
		foreach ($_REQUEST as $arg => $val)
			${$arg} = $val;
		if ($oper == 'add') {
			$id = Shop::CreateNewElementTreeNS($this->dbi, 'category', $id, $parent_id, $name);
			if ($id == false) {
				$response->success = false;
				$response->message = 'Возникла ошибка при добавлении!<br>Сообщите разработчику!';
				$response->new_id = 0;
			} else {
				$response->success = true;
				$response->message = '';
				$response->new_id = $id;
			}
			echo json_encode($response);
		}
		if ($oper == 'edit') {
			$response->success = Shop::SetNewNameforElementTreeNS($this->dbi, 'category', $id, $name);
			$response->message = 'Возникла ошибка сохранения изменений!<br>Сообщите разработчику!';
			$response->new_id = 0;
			echo json_encode($response);
		}
		if ($oper == 'del') {
			//$response->success = DeleteElementTreeNS('category',$id);
			$response->success = Shop::MoveElementTreeNS($this->dbi, 'category', $id, 90);
			$response->message = 'Возникла ошибка при удалении!<br>Сообщите разработчику!';
			$response->new_id = 0;
			echo json_encode($response);
		}
		if ($oper == 'copy') {
			//echo CopyTreeByID('category',$source,$target);
			echo Shop::CopyTreeNS($this->dbi, 'category', $source, $target);
		}
		if ($oper == 'move') {
			//echo SetParentIDforTree('category','CatID',$source,$target);
			echo Shop::MoveElementTreeNS($this->dbi, 'category', $source, $target);
		}
	}
	public function add_in_cat() {
		foreach ($_REQUEST as $arg => $val)
			${$arg} = $val;
		echo Shop::AddToCategory($this->dbi, $cat_id, $source);
	}
	public function del_from_cat() {
		foreach ($_REQUEST as $arg => $val)
			${$arg} = $val;
		echo Shop::DelFromCategory($this->dbi, $cat_id, $source);
	}

//test grid for print result
	function printResultSet(&$rowset, $i) {
		echo "Result set $i:<br>";
		foreach ($rowset as $row) {
			foreach ($row as $col) {
				echo $col . " | ";
			}
			echo '<br>';
		}
		echo '<br>';
	}
//
	private function echo_response($stmt) {
		$response = new stdClass();
		$response->new_id = 0;
		$response->success = Fn::checkErrorMySQLstmt($stmt);
		$response->sql_message = "";
		if ($response->success != true) {
			$response->message = 'Возникла ошибка при внесении информации!<br><br>Сообщите разработчику!';
			if (strlen($response->sql_message) == 0)
				$response->sql_message = $response->message;
			echo json_encode($response);
			return;
		}
		//$response->success = false;
		$rowset = $stmt->fetchAll(PDO::FETCH_BOTH);
//Fn::debugToLog("s", "1");
		foreach ($rowset as $row) {
//Fn::debugToLog("s", "2");
			if ($response->success)
//Fn::debugToLog("s", json_encode($row));
				$response->success = $row[0];
				$response->new_id = $row[1];
				$response->sql_message = $row[2];
			break;
		}
		if ($response->success == true) {
			$response->message = 'Информация успешно сохранена!';
			if (strlen($response->sql_message) == 0)
				$response->sql_message = $response->message;
			echo json_encode($response);
		} else {
			$response->message = 'Вы ничего не изменили!';
			if (strlen($response->sql_message) == 0)
				$response->sql_message = $response->message;
			echo json_encode($response);
		}
		return;
	}
}
?>
