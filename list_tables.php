<?php
$c = new mysqli('127.0.0.1', 'root', '', 'explora_db');
if ($c->connect_error) {
    die("Connection failed: " . $c->connect_error);
}
$r = $c->query('SHOW TABLES');
while($row = $r->fetch_row()) {
    echo $row[0]."\n";
}
