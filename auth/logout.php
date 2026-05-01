<?php
session_start();
session_destroy();
header("Location: /chargealaya/index.php");
exit();
?>
