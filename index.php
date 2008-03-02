<?php
	# Change these!
	$main_url = "http://localhost/";
	$password = "";
	
	$logged_in = (empty($password) or (isset($_COOKIE['uploader_password']) and $_COOKIE['uploader_password'] == md5($password)) or (isset($_POST['api_password']) and $_POST['api_password'] == $password));
	$directory = (empty($_GET['dir'])) ? "." : $_GET['dir'] ;
	
	if (isset($_GET['login']) and !empty($_POST['password'])) {
		if ($_POST['password'] == $password) {
			setcookie("uploader_password", md5($_POST['password']), time() + 60 * 60 * 24 * 30, "/");
			header("Location: ".$main_url);
			exit;
		} else {
			$message = '<p id="oh-no">Password incorrect.</p>';
		}
	}
	
	if ($logged_in) {
		function rmbig($dir) { # Via WebCheatCheet.com
			$dhandle = opendir($dir);

			if ($dhandle) {
				while (false !== ($fname = readdir($dhandle))) {
					if (is_dir( "{$dir}/{$fname}" )) {
						if (($fname != '.') && ($fname != '..')) {
							rmbig("$dir/$fname");
						}
					} else {
						unlink("{$dir}/{$fname}");
					}
				}
				closedir($dhandle);
			}
		
			rmdir($dir);
		}
	
		function mkpath($path) { # Via Zingus J. Rinkle
			if(@mkdir($path) or file_exists($path)) return true;
			return (mkpath(dirname($path)) and mkdir($path));
		}
	
		function random($length) { // Via http://us.php.net/rand
			$pattern = "1234567890abcdefghijklmnopqrstuvwxyz";
			$key = $pattern{rand(0,35)};
			for($i=1; $i < $length; $i++) {
				$key.= $pattern{rand(0,35)};
			}
			return $key;
		}
	
		if (!empty($_FILES['add']) and isset($_GET['add'])) {
			$strip = array(
				'~', '`', '!', '@', '#', '$', '%', '^', '&', '*',
				'(', ')', '-', '_', '=', '+', '[', '{', ']', '}',
				'\\', '|', ';', ':', '"', '\'', ',', '<', '>', '/',
				'?', ' '
			);
	
			$ext = end(explode(".", $_FILES['add']['name']));

			$clean = str_replace($strip, '-', $_FILES['add']['name']);
	
			if (file_exists($directory."/".$clean)) {
				$clean = str_replace(".".$ext, "", $clean);
				$clean = $clean.random(3);
				$clean = $clean.".".$ext;
			}

			function newname($name) {
				global $directory, $ext;
	
				if (file_exists($directory."/".$name)) {
					$clean = str_replace(".".$ext, "", $name);
					$clean = $clean.random(3);
					$clean = $clean.".".$ext;
					if (!file_exists($directory."/".$clean)) return $clean;
				} else {
					return $name;
				}
		
				return newname($clean);
			}
		
			$strip_dot = ($directory == ".") ? "" : $directory."/" ;
			if (@move_uploaded_file($_FILES['add']['tmp_name'], $directory."/".$clean))
				$message = '<p id="oh-yeah"><a href="'.$main_url.$strip_dot.$clean.'">'.$clean.'</a></p>';
			else
				$message = '<p id="oh-no">An error has occured!</p>';
		}
	
		if (isset($_POST['xml']) and $_POST['xml'] == "yes" and isset($_GET['add']) and isset($_FILES['add'])) {
			header("Content-type: text/xml");
?>
<<?=''?>?xml version="1.0" encoding="iso-8859-1"?<?=''?>>
<links>
	<file_link><?php echo $main_url.$strip_dot.$clean; ?></file_link>
	<file_ext><?php echo $ext; ?></file_ext>
	<file_name><?php echo $clean; ?></file_name>
	<file_folder><?php echo $directory; ?></file_folder>
</links>
<?php
			exit;
		}
	
		if (!empty($_POST['_delete'])) {
			$replace = array("_SLASH_" => "/", "_DOT_" => ".");
			$target = str_replace(array_keys($replace), array_values($replace), $_POST['_delete']);
			if (is_dir($target)) {
				rmbig($target);
				exit;
			} else {
				unlink($target);
				exit;
			}
		}
	
		if (!empty($_POST) and isset($_GET['mkdir'])) {
			if (strstr($_POST['folder'], "/"))
				if (mkpath($directory."/".$_POST['folder']))
					$message = '<p id="oh-yeah">Directory created!</code></p>';
				else
					$message = '<p id="oh-no">Could not create directory.</p>';
			else
				if (mkdir($directory."/".$_POST['folder']))
					$message = '<p id="oh-yeah">Directory created!</code></p>';
				else
					$message = '<p id="oh-no">Could not create directory.</p>';
		
		}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
		<title>File Browser</title>
		<link rel="stylesheet" href="includes/stylesheets/base.css" type="text/css" media="screen" title="no title" charset="utf-8" />
		<link rel="stylesheet" href="includes/stylesheets/screen.css" type="text/css" media="screen" title="no title" charset="utf-8" />
		<script src="includes/javascript.js" type="text/javascript" charset="utf-8"></script>
	</head>
	<body>
		<?php if (!empty($message)) echo $message; ?>
		<form action="<?php echo ($directory == ".") ? "?add" : "?dir=".$directory."&add" ; ?>" enctype="multipart/form-data" method="post">
			<input name="add" type="file" />
			<input type="hidden" name="blank" value="bugfix" id="blank" />
			<input type="submit" value="Go!" />
		</form>
		<table>
			<thead>
				<tr>
					<td>Filetype</td>
					<td>Filename</td>
					<td>Delete</td>
				</tr>
			</thead>
			<tbody>
<?php
	if (strpos($directory, "/"))
		$path = str_replace("/".substr(strrchr($directory, "/"), 1), "", $directory);
	else
		$path = $main_url;
		
	$link = (is_dir($path)) ? "?dir=".$path : $path ;
?>
				<tr id="_DOT__DOT_">
					<td class="filetype"><img src="includes/images/up.png" alt="up" /></td>
					<td class="filename"><a href="<?php echo $link; ?>">Parent Directory</a> <a href="<?php echo $path; ?>"><img src="includes/images/bullet_go.png" alt="bullet_go" /></a></td>
					<td class="delete">&nbsp;</td>
				</tr>
<?php
	$open = opendir($directory);
	while (false !== ($list = readdir($open))) {
		if (substr($list, 0, 1) == "." or ($directory == "." and ($list == "index.php" or $list == "includes" or $list == "Icon?"))) continue;
		$type = "page_white";
		$path = ($directory == ".") ? $list : $directory."/".$list ;
		
		if (!is_dir($path)) {
			$ext = end(explode(".", $list));

			switch ($ext) {
				case "ai": case "eps": case "gif": case "jpeg": case "jpg": case "png": case "psd": $type = "picture"; break;
				case "as": case "css": case "html": case "js": case "php": case "phps": case "pl": case "py": case "rb": case "rhtml": case "rjs": case "xml": case "yml": $type = "page_code"; break;
				case "avi": case "flv": case "mov": case "mpeg": case "mpg": case "swf": case "wmv": $type = "film"; break;
				case "bz2": case "gzip": case "rar": case "tar": case "zip": $type = "package"; break;
				case "doc": case "docx": case "ppt": case "xls": $type = "page_white_office"; break;
				case "flac": case "m4a": case "mid": case "mp3": case "wav": $type = "sound"; break;
				case "fon": case "otf": case "ttf": $type = "font"; break;
				case "pdf": case "rtf": case "txt": $type = "page_white_text"; break;
				case "swf": case "fla": $type = "page_white_flash"; break;

				default: $type = "page_white"; break;
			}
		}
		if (is_dir($path)) $type = "folder";
		$link = (is_dir($path)) ? "?dir=".$path : $path ;
		$clean_dir = ($directory == ".") ? "" : $directory."/" ;
		$clean = str_replace("/", "_SLASH_", $clean_dir.$list);
		$clean = str_replace(".", "_DOT_", $clean);
		if ($type == "picture") {
			list($width, $height, $type, $attr) = getimagesize($link);
			$icon = ($width <= 16 and $height <= 16) ? $link : "includes/images/picture.png" ;
		} else {
			$icon = "includes/images/".$type.".png";
		} 
?>
				<tr id="<?php echo $clean; ?>">
					<td class="filetype"><img src="<?php echo $icon; ?>" alt="<?php echo $type; ?>" /></td>
					<td class="filename"><a href="<?php echo $link; ?>"><?php echo $list; ?></a><?php if (is_dir($path)): ?> <a href="<?php echo $path; ?>"><img src="includes/images/bullet_go.png" alt="bullet_go" /></a><?php endif; ?></td>
					<td class="delete"><a href="javascript:_delete('<?php echo $clean; ?>')"><img src="includes/images/cancel.png" alt="" /></a></td>
				</tr>
<?php
	}
	closedir($open);
?>
			</tbody>
		</table>
		<form action="<?php echo ($directory == ".") ? "?mkdir" : "?dir=".$directory."&mkdir" ; ?>" method="post">
			<input type="text" name="folder" value="" id="folder" />
			<input type="hidden" name="blank" value="bugfix" id="blank" />
			<input type="submit" value="Create Folder" />
		</form>
<?php
	} else {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
		<title>File Browser</title>
		<link rel="stylesheet" href="includes/stylesheets/base.css" type="text/css" media="screen" title="no title" charset="utf-8" />
		<link rel="stylesheet" href="includes/stylesheets/screen.css" type="text/css" media="screen" title="no title" charset="utf-8" />
		<script src="includes/javascript.js" type="text/javascript" charset="utf-8"></script>
	</head>
	<body>
		<?php if (!empty($message)) echo $message; ?>
		<form action="?login" enctype="multipart/form-data" method="post">
			<input type="password" name="password" value="password" id="password" />
			<input type="submit" value="Go!" />
		</form>
<?php
	}
?>	
	</body>
</html>