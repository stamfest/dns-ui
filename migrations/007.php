<?php
$migration_name = 'Implement database based login';

// Add new value PHP_AUTH to enum type auth_realm.
$this->database->exec('ALTER TABLE "user" ADD COLUMN password TEXT');
// Add initial "admin" user with password "changeme"
$this->database->exec("INSERT INTO \"user\" (uid,password,active,admin, auth_realm) VALUES ('admin','$2y$10$2A/zVOViua7CFjzx1LhXOOt9gaiiK/LohYdFHB486cQdWtZd4UZli', 1, 1, 'local')");

