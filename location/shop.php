<?php

if (!defined('MCR')) exit;
if (!defined('EX')) exit ("К сожалению, модуль несовместим с webMCR. Установите его форк <a href=\"http://git.worldsofcubes.net/webmcrex\">webMCRex</a>");

if (isset($_REQUEST['do'])) $do = $_REQUEST['do'];
else $do = 'servers';
if ($do == 'server_add') $do = 'server_edit';
if ($do == 'item_add') $do = 'item_edit';
if ($do == 'list' and !isset($_GET['server'])) $do = 'servers';
$path = "shop/";
$p = (isset($_REQUEST['p'])) ? (int) $_REQUEST['p'] : 1;
loadTool("shop.class.php", "shop_mod/");
$shop_mod = new ShopMod();
if ($shop['install']) $do = 'install';
switch ($do) {
	case 'install':
		if (!$shop['install']) accss_deny();
		if(!$user or $user->lvl() < 15) accss_deny();
		if(isset($_POST['key'])){
			if($result = $shop_mod->install($_POST['key'])) {
				$page_c = 'shop_install.html';
				switch ($result) {
					case 101:
						$content_main .= View::Alert("Неверно составлен запрос к API магазина. Обратитесь к разработчикам.");
						break;
					case 102:
						$content_main .= View::Alert("Эта сборка не должна стоять на этом домене!");
						break;
					case 103:
						$content_main .= View::Alert("Ключ неверен для этого домена!");
						break;
					case 104:
						$content_main .= View::Alert("Произошла недокументированная ошибка WorldsOfCubes Store API.");
						break;
					default:
						$content_main .= View::Alert("Произошла недокументированная ошибка модуля. Код ошибки:" . $result);
						break;
				}
			} else $page_c = 'shop_installed.html';
		} else $page_c = 'shop_install.html';
		$content_main .= View::ShowStaticPage($page_c, $path);
		$page = "Установка ShopEX";
		break;
	case 'redeem':
		if(!$user or $user->lvl() < 1) accss_deny();
		if (isset($_POST['key'])) {
			loadTool("shopcommon.class.php", "shop_mod/");
			$key = new ShopKey($_POST['key']);
			$content_main = ($key->redeem()) ? View::Alert("Ключ успешно активирован", "success") : View::Alert("Введен неверный или недейсвительный ключ");
		}
		$content_main .= View::ShowStaticPage('key_redeem.html', $path);
		$page = "Активировать подарочный ключ";
		break;
	case 'key_adm':
		if(!$user or $user->lvl() < 15) accss_deny();
		$nbp = 20;
		$query = $db->execute("SELECT * FROM `shop_keys` LIMIT " . $nbp * ($p - 1) . ", $nbp");
		if($db->num_rows($query)) {
			ob_start();
			while ($temp = $db->fetch_array($query)) {
				include View::Get('key_item.html', $path);
			}
			$key_list = ob_get_clean();
		} else $key_list = View::ShowStaticPage('no_keys.html', $path);

		ob_start();
		include View::Get('key_list.html', $path);
		$content_main = ob_get_clean();
		if ($db->num_rows($query)) {
			$view = new View("shop/");
			$line = $db->execute("SELECT COUNT(*) FROM `shop_keys`");
			$line = $db->fetch_array($line);
			$content_main .= $view->arrowsGenerator("go/shop/key_adm/", $p, $line[0], $nbp, "pagin");
		}
//TODO Концепт: список ваучеров, редактирование в модали, AJAX (?) и форма добавления внизу

		$page = "Управление подарочными ключами";
		break;
	case 'cat_adm':
/* Meow */
		if(!$user or $user->lvl() < 15) accss_deny();
		$query = $db->execute("SELECT * FROM `shop_cats` ORDER BY `priority` DESC");
		if($db->num_rows($query)) {
			ob_start();
			while ($temp = $db->fetch_array($query)) {
				include View::Get('cat_item.html', $path);
			}
			$cat_list = ob_get_clean();
		} else $cat_list = View::ShowStaticPage('no_cats.html', $path);

		ob_start();
		include View::Get('cat_list.html', $path);
		$content_main = ob_get_clean();
//TODO Концепт: список категорий, редактирование в модали, AJAX (?) и форма добавления внизу

		$page = "Управление категориями";
		break;
	case 'buy':
		loadTool("shopcommon.class.php", "shop_mod/");
		$item = new ShopItem($_GET['id']);
		if ($item->id == -1) show_error('404', 'Товар не найден');
		if ($user and $user->lvl() > 1 and isset($_POST['submit'])) {
			$amount = InputGet('amount', 'POST', 'int');
			$server = InputGet('server', 'POST', 'int');
			if ($amount > 0) {
				switch ($item->buy($amount, $server)) {
					case 0: $content_main = View::Alert("Предмет успешно куплен", 'success'); break;
					case 1: $content_main = View::Alert("Выбранного сервера не существует!"); break;
					case 2: $content_main = View::Alert("Недостаточно денег!"); break;
				}
			} else $content_main = View::Alert("Введите число большее нуля!");
		}
		$server = new ShopServer($item->server);
		$cat = new ShopCat($item->cid);
		$servers_select = '';
		if($item->server == 0) {
			$servers = $db->execute("SELECT * FROM `shop_servers` ORDER BY `title` ASC");
			while($server_row = $db->fetch_array($servers))
				$servers_select .= "<option value=\"{$server_row['id']}\">{$server_row['title']}</option>";
		}
		ob_start();
		include View::Get("item_view.html", $path);
		$content_main .= ob_get_clean();
		$page = "Просмотр товара - " . $item->title;
		break;
	case 'item_edit':
		if(!$user or $user->lvl() < 15) accss_deny();
		$is_editing = isset($_GET['id']);
		loadTool("shopcommon.class.php", "shop_mod/");
		$query = $db->execute("SELECT `id`, `title` FROM `shop_cats` ORDER BY `priority` DESC");
		if (isset($_GET['id'])) {
			$what = 'Редактировать';
			$sitem = new ShopItem($_GET['id']);
			$title = (isset($_POST['title'])) ? $_POST['title'] : $sitem->title;
			$description = (isset($_POST['description'])) ? $_POST['description'] : $sitem->description;
			$item = (isset($_POST['item'])) ? $_POST['item'] : $sitem->item;
			$extra = (isset($_POST['extra'])) ? $_POST['extra'] : $sitem->extra;
			$type = (isset($_POST['type'])) ? $_POST['type'] : $sitem->type;
			$cid = (int) (isset($_POST['cid'])) ? $_POST['cid'] : $sitem->cid;
			$pic = (isset($_POST['pic'])) ? $_POST['pic'] : $sitem->pic;
			$price = (float) (isset($_POST['price'])) ? $_POST['price'] : $sitem->price;
			$realprice = $sitem->realprice;
			$discount = (float) (isset($_POST['discount'])) ? $_POST['discount'] : $sitem->discount;
			$num = (int) (isset($_POST['num'])) ? $_POST['num'] : $sitem->num;
			$server = (int) (isset($_POST['server'])) ? $_POST['server'] : $sitem->server;
			$cats = '';
			while($temp = $db->fetch_array($query)) {
				$cats .='<option value="' . $temp['id'] . '"' . (($cid == $temp['id'])? ' selected':'') . '>' . $temp['title'];
			}
			$query = $db->execute("SELECT `id`, `title` FROM `shop_servers`");
			$srvs = '<option value="0"' . (($server == 0)? ' selected':'') . '>Для всех серверов';
			while($temp = $db->fetch_array($query)) {
				$srvs .='<option value="' . $temp['id'] . '"' . (($server == $temp['id'])? ' selected':'') . '>' . $temp['title'];
			}
//			$ = (isset($_POST[''])) ? $_POST[''] : $sitem->;
			if (isset($_POST['submit'])) {
				$realprice = InputGet('realprice', 'POST', 'bool');
				$result = $sitem->update($title,$description,$item,$extra,$type,$cid,$pic,$price,$realprice,$discount,$num,$server);
				$content_main = ($result==0) ? View::Alert("Предмет отредактирован", "success") : View::Alert("Проверьте корректность данных");
			}
		} else {
			$what = 'Добавить';
			$title = (isset($_POST['title'])) ? $_POST['title'] : '';
			$description = (isset($_POST['description'])) ? $_POST['description'] : '';
			$item = (isset($_POST['item'])) ? $_POST['item'] : '';
			$extra = (isset($_POST['extra'])) ? $_POST['extra'] : '';
			$type = (isset($_POST['type'])) ? $_POST['type'] : 'item';
			$cid = (int) (isset($_POST['cid'])) ? $_POST['cid'] : '1';
			$pic = (isset($_POST['pic'])) ? $_POST['pic'] : '/style/Default/shop/img/missing_texture.png';
			$price = (float) (isset($_POST['price'])) ? $_POST['price'] : 0;
			$realprice = 1;
			$discount = (float) (isset($_POST['discount'])) ? $_POST['discount'] : 0;
			$num = (int) (isset($_POST['num'])) ? $_POST['num'] : 0;
			$server = (int) (isset($_POST['server'])) ? $_POST['server'] : 0;
			$cats = '';
			while($temp = $db->fetch_array($query)) {
				$cats .='<option value="' . $temp['id'] . '">' . $temp['title'];
			}
			$query = $db->execute("SELECT `id`, `title` FROM `shop_servers`");
			$srvs = '<option value="0">Для всех серверов';
			while($temp = $db->fetch_array($query)) {
				$srvs .='<option value="' . $temp['id'] . '">' . $temp['title'];
			}
			if (isset($_POST['submit'])) {
				$realprice = InputGet('realprice', 'POST', 'bool');
				$sitem = new ShopItem();
				$result = $sitem->create($title,$description,$item,$extra,$type,$cid,$pic,$price,$realprice,$discount,$num,$server);
				$content_main = (!$result) ? View::Alert("Предмет добавлен", "success") : View::Alert("Проверьте корректность данных");
			}
		}
		ob_start();
		include View::Get("item_create_edit.html", $path);
		$content_main .= ob_get_clean();
		$page = $what . " предмет";
		break;
	case 'server_edit':
		if(!$user or $user->lvl() < 15) accss_deny();
		$is_editing = isset($_GET['id']);
		$cantuseurl = array("install", "redeem", "key_adm", "list", "buy", "server_add", "server_edit", "servers", "item_add", "item_edit", "cat_adm");
		loadTool("shopcommon.class.php", "shop_mod/");
		if (isset($_GET['id'])) {
			$what = 'Редактировать';
			$server = new ShopServer($_GET['id']);
			$name = (isset($_POST['name'])) ? $_POST['name'] : $server->name;
			$url = (isset($_POST['url'])) ? $_POST['url'] : $server->url;
			$description = (isset($_POST['description'])) ? $_POST['description'] : $server->description;
			$pic = (isset($_POST['pic'])) ? $_POST['pic'] : $server->pic;
			if (isset($_POST['submit'])) {
				if (!in_array($url, $cantuseurl)) {
					$result = $server->update($name, $url, $description, $pic);
					$content_main = ($result) ? View::Alert("Данные о сервере обновлены", "success") : View::Alert("Проверьте корректность данных");
				} else $content_main = View::Alert("Нельзя использовать этот адрес для сервера! Он зарезервирован системой!");
			}
		} else {
			$what = 'Добавить';
			$name = (isset($_POST['name'])) ? $_POST['name'] : '';
			$url = (isset($_POST['url'])) ? $_POST['url'] : '';
			$description = (isset($_POST['description'])) ? $_POST['description'] : '';
			$pic = (isset($_POST['pic'])) ? $_POST['pic'] : '/style/Default/shop/img/missing_texture.png';
			if (isset($_POST['submit'])) {
				if (!in_array($url, $cantuseurl)) {
					$server = new ShopServer();
					$result = $server->create($name, $url, $description, $pic);
					$content_main = (!$result) ? View::Alert("Сервер добавлен в список", "success") : View::Alert("Проверьте корректность данных");
				} else $content_main = View::Alert("Нельзя использовать этот адрес для сервера! Он зарезервирован системой!");
			}
		}
		ob_start();
		include View::Get("server_create_edit.html", $path);
		$content_main .= ob_get_clean();

		$page = $what . " сервер";
		break;
	case 'servers':
		loadTool("shopcommon.class.php", "shop_mod/");
		if($user and $user->lvl() >= 15 and isset($_GET['delserver'])) {
			$server = new ShopServer($_GET['delserver']);
			if($server->id != -1)
				$server->delete();
			$content_main = View::Alert("Сервер удален", 'success');
		}
		$query = $db->execute("SELECT * FROM `shop_servers` ORDER BY `title` ASC");
		if (!$query or !$db->num_rows($query)) {
			ob_start();
			include View::Get("no_servers.html", $path);
			$content_main = ob_get_clean();
		} else {
			ob_start();
			include View::Get("servers_head.html", $path);
			for ($i = 0; $server = $db->fetch_array($query); $i = ($i + 1) % 4) {
				if ($i == 0) include View::Get("row_begin.html", $path);
				include View::Get("server.html", $path);
				if ($i == 3) include View::Get("row_end.html", $path);
			}
			if ($i != 0) include View::Get("row_end.html", $path);
			$content_main .= ob_get_clean();
		}
		$page = "Список серверов";
		break;
	default:
		loadTool("shopcommon.class.php", "shop_mod/");
		if($user and $user->lvl() >= 15 and isset($_GET['delitem'])) {
			$item = new ShopItem($_GET['delitem']);
			if($item->id != -1)
				$item->delete();
			$content_main = View::Alert("Предмет удален", 'success');
		}
		$server = new ShopServer($do, "url");
		if ($server->id < 1) show_error("404", "Сервер не найден");
		$nbp = 20;
		$p = (!isset($_GET['p'])) ? 1 : $_GET['p'];
		$cat_from_get = (!isset($_GET['cat']))? 'all' : $_GET['cat'];
		if ($cat_from_get != 'all') {
			$cat = new ShopCat($cat_from_get, 'url');
			if($cat->id < 1) show_error("404", "Категория не найдена");
			$additional_where = ' AND `cid`=' . $cat->id;
			$additional_url = $cat->url . '/';
		} else {
			$additional_url = '';
			$additional_where = '';
		}
		$first = $nbp * ($p - 1);
		$query = $db->execute("SELECT *
								FROM `shop_items`
								WHERE (`server`='" . $server->id . "' OR `server`=0) $additional_where
								ORDER BY `shop_items`.`item` ASC
								LIMIT $first, $nbp");
		if (!$query or !$db->num_rows($query)) {
			ob_start();
			include View::Get("no_items.html", $path);
			$content_main .= ob_get_clean();
		} else {
			$db_cats = $db->execute("SELECT `url`, `title`, `description` FROM `shop_cats` ORDER BY `priority` DESC");
			$cat_from_temp = array('url'=>'all','title'=>'Все','description'=>'');
			ob_start();
			do {
				include View::Get('items_cat.html', $path);
			} while ($cat_from_temp = $db->fetch_array($db_cats));
			$cats = ob_get_clean();
			ob_start();
			include View::Get("items_head.html", $path);
			for ($i = 0; $item = $db->fetch_array($query); $i = ($i + 1) % 4) {
				if ($i == 0) include View::Get("row_begin.html", $path);
				include View::Get("item.html", $path);
				if ($i == 3) include View::Get("row_end.html", $path);
			}
			if ($i != 0) include View::Get("row_end.html", $path);
			$content_main .= ob_get_clean();
			$view = new View("users/");
			$line = $db->execute("SELECT COUNT(*) FROM `shop_items` WHERE (`server`='" . $server->id . "' OR `server`=0)" . $additional_where);
			$line = $db->fetch_array($line);
			$content_main .= $view->arrowsGenerator("go/shop/" . $server->url . "/" . $additional_url, $p, $line[0], $nbp, "pagin");
		}
		$page = "Список товаров";
		break;
}

$content_main .= $shop_mod->ShowProtection();