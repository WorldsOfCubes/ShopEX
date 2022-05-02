<?php
/* WEB-APP : WebMCR (С) 2013 NC22 | License : GPLv3 */


if (empty($_REQUEST['method'])) exit;
$method = $_REQUEST['method'];

	require('../../system.php');
	loadTool('ajax.php');	
	loadTool('user.class.php');
	loadTool("shopcommon.class.php", "shop_mod/");

	$db = new DB();
	$db->connect('shop_action_'.$method);
	MCRAuth::userLoad();
switch ($method) {
	case "cat_edit":
		if(!$user or $user->lvl() < 15) aExit(1, 'Недостаточно прав!');
		$cat = new ShopCat($_POST['id']);
		$result = ($cat->id < 1)? $cat->create($_POST['title'], $_POST['url'], $_POST['description'], $_POST['priority']) : $cat->update($_POST['title'], $_POST['url'], $_POST['description'], $_POST['priority']);
		$message = ($result != 3)? ($result != 2)? ($result != 1)? "Успееех!" : "Адрес занят!" : "Адрес не может быть числом!" : "Приоритет не может быть строкой!";
		aExit($result, $message);
		break;
	case "key_edit":
		if(!$user or $user->lvl() < 15) aExit(1, 'Недостаточно прав!');
		$key = new ShopKey($_POST['key']);
		$result = ($key->id < 1)? $key->create($_POST['amount'], $_POST['price'], ((InputGet('realprice', 'POST', 'bool'))?1:0)) : $key->update($_POST['amount'], $_POST['price'], ((InputGet('realprice', 'POST', 'bool'))?1:0));
		$message = ($result!=0)? ($result == 2)? "Неверный формат ключа" : "Такой ключ уже существует" : "Успееех!";
		aExit($result, $message);
		break;
	case "key_delete":
		if(!$user or $user->lvl() < 15) aExit(1, 'Недостаточно прав!');
		$key = new ShopKey($_REQUEST['key']);
		if(!$key->id <=1)
			$key->delete();
		aExit(0, '');
		break;
	case "cat_delete":
		if(!$user or $user->lvl() < 15) aExit(1, 'Недостаточно прав!');
		$cat = new ShopCat($_REQUEST['id']);
		if(!$cat->id <=1)
			$cat->delete();
		aExit(0, '');
		break;
	default:
		aExit(1, 'Неверно задано действие!');
	break;
}