<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>user</title>
</head>
<body>
	<script>
		var xhr = new XMLHttpRequest();
		xhr.open('get', '<?php echo U('index');?>');
		xhr.send(null);
		xhr.onreadystatechange = function () {
			if(xhr.status == 200 && xhr.readyState == 4) {
				var a = JSON.parse( xhr.responseText );
				for (var i=0; i<a.length; i++) {
					document.write('<li>' + a[i].user + '</li>');
				}
			}
		}
	</script>
</body>
</html