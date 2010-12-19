<?php
set_time_limit(3600);

define('DB_HOST', 'db_host');
define('DB_NAME', 'db_name');
define('DB_USER', 'db_user');
define('DB_PASSWORD', 'db_pass');
define('DEBUG', true);

$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);

if (!$link) die('Could not connect: ' . mysql_error()); 
$db= mysql_select_db(DB_NAME, $link);
if (!$db) die ('Can\'t use '.DB_NAME.': ' . mysql_error($link)); 

$base_path = dirname(__FILE__).'/wp-content/shopp/images/';
if(!file_exists($base_path)) mkdir($base_path, 0757, true); 
// ^^ change file perms accordingly

$sql = "select id, parent as pid, value, context
from wp_shopp_meta m 
where m.type='image' 
and m.context in ('product','category')
";

$result = mysql_query($sql,$link);

if (!$result) die(mysql_error($link). " " . $sql); 
if(DEBUG) print "<p>converting images...</p>";

while ($row = mysql_fetch_assoc($result)) {
	extract($row);
	$meta = unserialize($value);
	$ass = get_ass($meta->uri);
	$parent = get_parent($pid);
	if($meta->storage == 'DBStorage') {
		$file = "{$base_path}{$id}-{$context}";
		$img = img($meta, $ass, $file);
		$meta->storage = 'FSStorage';
		$meta->uri = $meta->filename = $img;
	}
	save($id, $meta);
}

function save($id, $meta) {
	global $link;
	$cornflakes = serialize($meta);
	$sql = "update wp_shopp_meta set value='{$cornflakes}' where id = '{$id}'";
	$result = mysql_query($sql,$link);
	if (!$result) die(mysql_error($link). " " . $sql); 
}

function get_ass($uri) {
	global $link;
	$row=array();
	$sql = "select data, size, datatype from wp_shopp_asset where id = '$uri'";
	$result = mysql_query($sql,$link);
	if ($result) $row = mysql_fetch_object($result); 
	return $row;
}

function get_parent($pid) {
	global $link;
	$row=array();
	$sql = "select * from wp_shopp_product where id = '$pid'";
	$result = mysql_query($sql,$link);
	if ($result) $row = mysql_fetch_object($result); 
	return $row;
}

function img($meta, $ass, $file) {
	$res = false;
	$data = imagecreatefromstring($ass->data);
	switch(strtolower($meta->mime)) {
		case 'image/jpeg': $file .= ".jpg"; $res = imagejpeg($data, $file, 90); break;
		case 'image/png': $file .= ".png"; $res = imagepng($data, $file, 90); break;
		case 'image/gif': $file .= ".gif"; $res = imagegif($data, $file, 90); break;
		default: break;
   	}
	if(DEBUG) print "image ".$file." saved? " . ($res ? 'yes' : 'no') . "<br/>\n";
	return basename($file);
}


mysql_close($link);
if(DEBUG) print "<p>DONE converting images...</p>";

