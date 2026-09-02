<?php

echo "Storage exists: ";
var_dump(is_link(__DIR__.'/storage'));

echo "<br><br>";

echo "Storage path exists: ";
var_dump(file_exists(__DIR__.'/storage'));

echo "<br><br>";

echo realpath(__DIR__.'/storage');