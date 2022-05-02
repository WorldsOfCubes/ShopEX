<?php
//===========================================
//ShopEX Common Classes
//Created by Zhirov Sergey in September 2014
//===========================================
class ShopItem {
	public $id;
	public $title;
	public $description;
	public $item;
	public $extra;
	public $type;
	public $cid;
	public $pic;
	public $price;
	public $realprice;
	public $discount;
	public $num;
	public $server;
	public function __construct($id = null) {
		global $db;
		$query = $db->execute("SELECT * FROM `shop_items` WHERE `id`='" . $id . "';");
		if($db->num_rows($query) != 1){
			$this->id = -1;
		} else {
			$query = $db->fetch_assoc($query);
			$this->id = $query['id'];
			$this->title = $query['title'];
			$this->description = $query['description'];
			$this->item = $query['item'];
			$this->extra = $query['extra'];
			$this->type = $query['type'];
			$this->cid = $query['cid'];
			$this->pic = $query['pic'];
			$this->price = $query['price'];
			$this->realprice = $query['realprice'];
			$this->discount = $query['discount'];
			$this->num = $query['num'];
			$this->server = $query['server'];
		}
	}
	public function create($title, $description, $item, $extra, $type, $cid, $pic, $price, $realprice, $discount, $num, $server) {
		global $db;
		$check = new ShopServer($server);
		if(!$check->id) return 1;
		$check = new ShopCat($cid);
		if(!$check->id) return 2;
		$types = array('item','money', 'rgown', 'rgmem','perm','permgroup');
		if(!in_array($type,$types)) return 3;
		$query = $db->execute("INSERT INTO `shop_items` (`title`, `description`, `item`, `extra`, `type`, `cid`, `pic`, `price`, `realprice`, `discount`, `num`, `server`)"
							. " VALUES ('{$db->safe($title)}', '{$db->safe($description)}', '{$db->safe($item)}', '{$db->safe($extra)}', '{$db->safe($type)}', '{$db->safe($cid)}', '{$db->safe($pic)}', '{$db->safe($price)}', '{$db->safe($realprice)}', '{$db->safe($discount)}', '{$db->safe($num)}', '{$db->safe($server)}');");
		if(!$query) return 4;
		$id = $db->insert_id();
		$this->__construct($id);
		return 0;
	}
	public function update($title, $description, $item, $extra, $type, $cid, $pic, $price, $realprice, $discount, $num, $server) {
		global $db;
		$check = new ShopServer($server);
		if(!$check->id and $server != 0) return 1;
		$check = new ShopCat($cid);
		if(!$check->id) return 2;
		$types = array('item','money', 'rgown', 'rgmem','perm','permgroup');
		if(!in_array($type,$types)) return 3;
		return ($db->execute("UPDATE `shop_items` SET `title`='{$db->safe($title)}',
													  `description` = '{$db->safe($description)}',
													  `item` = '{$db->safe($item)}',
													  `extra` = '{$db->safe($extra)}',
													  `type` = '{$db->safe($type)}',
													  `cid` = '{$db->safe($cid)}',
													  `pic` = '{$db->safe($pic)}',
													  `price` = '{$db->safe($price)}',
													  `realprice` = '{$db->safe($realprice)}',
													  `discount` = '{$db->safe($discount)}',
													  `num` = '{$db->safe($num)}',
													  `server` = {$db->safe($server)} WHERE `id`={$this->id};"))? false : true;
	}
	public function buy($amount, $server = 0) {
		global $db, $user;
		$server = ($this->server == 0)? $server : $this->server;
		$check = new ShopServer($server);
		$price = ($this->num < $amount and $this->num)?
					$this->num * ($this->price - $this->discount) + $this->price * ($amount - $this->num):
					$amount * ($this->price - $this->discount);
		if($check->id < 0) return 1;
		if((($this->realprice)? $user->getMoney() : $user->getEcon()) < $price)
			return 2;
		if($this->num and $this->num < $amount) {
			$db->execute("UPDATE `shop_items` SET `num`=0, `discount`=0 WHERE `id`={$this->id}");
			$this->num = 0;
			$this->discount = 0;
		} elseif ($this->discount and $this->num) {
			$db->execute("UPDATE `shop_items` SET `num`=`num`-$amount WHERE `id`={$this->id}");
			$this->num -= $amount;
		}
		($this->realprice)? $user->addMoney(-$price) : $user->addEcon(-$price);
		if(strpos($this->item, '?lifetime=')){
			$item = explode('?lifetime=', $this->item);
			$item = $item[0] . '?lifetime=' . $item[1] * $amount;
			$amount = 1;
		} else $item = $this->item;
		$db->execute("INSERT INTO `shop_cart_$server` (`title`, `item`, `extra`, `type`, `player`, `amount`)"
			. " VALUES ('{$this->title}', '$item', '{$this->extra}', '{$this->type}', '" . $user->name() . "', '$amount');");
		return 0;
	}
	public function delete() {
		global $db;
		if ($this->id < 1) return;
		$db->execute("DELETE FROM `shop_items` WHERE `id`='".$this->id."';");
	}
}

class ShopServer {
	public $id;
	public $name;
	public $description;
	public $url;
	public $pic;
	public function __construct($someth = null, $what = "id") {
		global $db;
		$query = $db->execute("SELECT * FROM `shop_servers` WHERE `$what`='$someth';");
		if($db->num_rows($query) != 1){
			$this->id = -1;
		} else {
			$query = $db->fetch_assoc($query);
			$this->id = $query['id'];
			$this->name = $query['title'];
			$this->url = $query['url'];
			$this->description = $query['description'];
			$this->pic = $query['pic'];
		}
	}
	public function create($name, $url, $description, $pic) {
		global $db;
		$query = $db->execute("INSERT INTO `shop_servers` (`title`, `url`, `description`, `pic`) VALUES ('{$db->safe($name)}', '{$db->safe($url)}', '{$db->safe($description)}', '{$db->safe($pic)}');");
		if(!$query) return 1;
		$id = $db->insert_id();
		$query = $db->execute("CREATE TABLE `shop_cart_$id` (
				`id` BIGINT(20) NOT NULL AUTO_INCREMENT,
				`title` VARCHAR(255) NOT NULL,
				`item` VARCHAR(80) NOT NULL,
				`extra` TEXT NULL ,
				`type` VARCHAR(80) NOT NULL,
				`player` VARCHAR(255) NOT NULL,
				`amount` BIGINT(20) NOT NULL,
				PRIMARY KEY (`id`)
			) DEFAULT CHARSET=utf8 ENGINE=MyISAM;");
		if(!$query) {
			$this->id = $id;
			$this->delete();
			return 2;
		}
		$this->__construct($id);
		return 0;
	}
	public function update($name, $url, $description, $pic) {
		global $db;
		return ($db->execute("UPDATE `shop_servers` SET  `title`='{$db->safe($name)}',
														 `url`='{$db->safe($url)}',
														 `description` = '{$db->safe($description)}',
														 `pic` = '{$db->safe($pic)}' WHERE `id`={$this->id};"))? true : false;
	}
	public function delete() {
		global $db;
		$db->execute("DELETE FROM `shop_servers` WHERE `id`='".$this->id."';");
		$db->execute("DELETE FROM `shop_items` WHERE `server`='".$this->id."';");
		$db->execute("DROP TABLE IF EXISTS `shop_cart_{$this->id}`;");
	}
}

class ShopCat {
	/* Meow */
	public $id;
	public $name;
	public $description;
	public $url;
	public $priority;
	public function __construct($someth = null, $what = "id") {
		global $db;
		$query = $db->execute("SELECT * FROM `shop_cats` WHERE `$what`='" . $someth . "';");
		if($db->num_rows($query) != 1){
			$this->id = -1;
		} else {
			$query = $db->fetch_assoc($query);
			$this->id = $query['id'];
			$this->name = $query['title'];
			$this->url = $query['url'];
			$this->description = $query['description'];
			$this->priority = $query['priority'];
		}
	}
	public function create($name, $url, $description, $priority) {
		global $db;
		if(!preg_match("/^[0-9]+$/", $priority)) return 3;
		if(is_int($url)) return 2;
		$query = $db->execute("INSERT INTO `shop_cats` (`title`, `url`, `description`, `priority`) VALUES ('{$db->safe($name)}', '{$db->safe($url)}', '{$db->safe($description)}', {$db->safe($priority)});");
		if(!$query) return 1;
		$this->id = $db->insert_id();
		$this->__construct($this->id);
		return 0;
	}
	public function update($name, $url, $description, $priority) {
		global $db;
		if(!preg_match("/^[0-9-]+$/", $priority)) return 3;
		if(is_int($url)) return 2;
		return ($db->execute("UPDATE `shop_cats` SET `title`='{$db->safe($name)}',
														`url`='{$db->safe($url)}',
														 `description` = '{$db->safe($description)}',
														 `priority` = {$db->safe($priority)} WHERE `id`={$this->id};"))? 0 : 1;
	}
	public function delete() {
		global $db;
		if ($this->id < 1) return;
			$db->execute("UPDATE `shop_items` SET `cid`=1 WHERE `cid`={$this->id};");
		$db->execute("DELETE FROM `shop_cats` WHERE `id`='".$this->id."';");
	}
}

class ShopKey {
	public $amount;
	public $price;
	public $is_real;
	public $key;
	public $id;
	public function __construct($key) {
		global $db;
		$query = $db->execute("SELECT * FROM `shop_keys` WHERE `key`='" . $db->safe($key) . "';");
		if($db->num_rows($query) != 1){
			$this->amount = -1;
			$this->key = $key;
		} else {
			$query = $db->fetch_assoc($query);
			$this->amount = $query['amount'];
			$this->price = $query['price'];
			$this->is_real = $query['realprice'];
			$this->key = $query['key'];
			$this->id = $query['id'];
		}
	}
	public function create($amount, $price, $is_real) {
		global $db;
		if(!$this->check()) return 2;
		if($db->execute("INSERT INTO `shop_keys` (`amount`, `key`, `realprice`, `price`) VALUES ({$db->safe($amount)}, '{$this->key}', {$db->safe($is_real)}, {$db->safe($price)})")) {
			$this->__construct($this->key);
			return 0;
		} else return 1;
	}
	public function update($amount, $price, $is_real)
	{
		global $db;
		return ($db->execute("UPDATE `shop_keys` SET `amount`='{$db->safe($amount)}',
													 `price`='{$db->safe($price)}',
													 `realprice` = '{$db->safe($is_real)}'
													  WHERE `id`={$this->id};")) ? 0 : 1;
	}
	private function check() {
		$key = explode("-", $this->key);
		if(is_array($key) and (count($key) == 4)) {
			for($i = 0; $i <= 3; $i++){
				if((!preg_match("/^[A-Z0-9]+$/", $key[$i])) or (strlen($key[$i]) != 5))
					return false;
			}
			return true;
		}

		return false;
	}
	public function redeem() {
		global $user, $db;
		if($user and ($this->amount > 0) and $this->check()) {
			$db->execute("UPDATE `shop_keys` SET `amount`=`amount`-1;") or print mysql_error();
			$db->execute("INSERT INTO `shop_keys_log` (`pid`, `kid`) VALUES (" . $user->id() . ", {$this->id})");
			($this->is_real)? $user->addMoney($this->price) : $user->addEcon($this->price);
			return true;
		}
		return false;
	}
	public function delete () {
		global $db;
		$db->execute("DELETE FROM `shop_keys` WHERE `id`='".$this->id."';");
	}
}