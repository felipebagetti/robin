<!DOCTYPE html>
<html>
<head>
    <meta charset="iso-8859-1">
    <script type="text/javascript">
        window.onload = function(){
        	window.location = '<?php echo Util::getBaseUrl() ?>?redir=' + encodeURIComponent(window.location.toString());
        }
    </script>
</head>
<body>
</body>
</html>