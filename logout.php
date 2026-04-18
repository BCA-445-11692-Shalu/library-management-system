<?php
// logout.php — Destroy student session
require_once 'includes/config.php';
session_destroy();
header('Location: index.php'); exit;
