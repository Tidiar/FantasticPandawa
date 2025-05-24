<?php
echo "<h3>Debug Info:</h3>";
echo "Current file location: " . __FILE__ . "<br>";
echo "Current directory: " . __DIR__ . "<br>";
echo "Document root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Script name: " . $_SERVER['SCRIPT_NAME'] . "<br>";
echo "Server name: " . $_SERVER['SERVER_NAME'] . "<br>";

echo "<h3>File Checks:</h3>";
echo "auth/logout.php exists: " . (file_exists('../auth/logout.php') ? 'YES' : 'NO') . "<br>";
echo "logout.php exists: " . (file_exists('../logout.php') ? 'YES' : 'NO') . "<br>";
echo "auth/login.php exists: " . (file_exists('../auth/login.php') ? 'YES' : 'NO') . "<br>";

echo "<h3>Directory Contents:</h3>";
echo "Parent directory contents:<br>";
$files = scandir('..');
foreach($files as $file) {
    if($file != '.' && $file != '..') {
        echo "- " . $file . (is_dir('../' . $file) ? ' (folder)' : ' (file)') . "<br>";
    }
}

if(is_dir('../auth')) {
    echo "<br>Auth folder contents:<br>";
    $authFiles = scandir('../auth');
    foreach($authFiles as $file) {
        if($file != '.' && $file != '..') {
            echo "- auth/" . $file . "<br>";
        }
    }
}
?>