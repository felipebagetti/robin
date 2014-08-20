<!DOCTYPE html>
<html>
<head>
<meta charset="iso-8859-1">
<?php
foreach($view->_css as $css){
    echo '<link rel="stylesheet" href="'.$css.'">'."\n";
}
foreach($view->_js as $js){
    echo '<script src="'.$js.'"></script>'."\n";
}
?>
<script type="text/javascript">  
<?php echo $view->_jsOnload; ?>
</script>
<title><?php echo $view->_title ?></title>
</head>
<body>
<?php include 'menu.html.php'; ?>
<div class="container-2">
    <div id="<?php echo substr($view->_container, 1); ?>"></div>
</div>
</body>
</html>