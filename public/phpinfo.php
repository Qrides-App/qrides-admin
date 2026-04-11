<?php
$requirements = [
    'PHP >= 8.1' => version_compare(PHP_VERSION, '8.1.0', '>='),
    'BCMath PHP Extension' => extension_loaded('bcmath'),
    'Ctype PHP Extension' => extension_loaded('ctype'),
    'JSON PHP Extension' => extension_loaded('json'),
    'Mbstring PHP Extension' => extension_loaded('mbstring'),
    'OpenSSL PHP Extension' => extension_loaded('openssl'),
    'PDO PHP Extension' => extension_loaded('pdo'),
    'Tokenizer PHP Extension' => extension_loaded('tokenizer'),
    'XML PHP Extension' => extension_loaded('xml'),
    'gRPC PHP Extension' => extension_loaded('grpc'),
    'Symlink Support' => function_exists('symlink'),
];

echo "<h2>Server Requirement Check</h2>";
echo "<table border='1' cellspacing='0' cellpadding='8'>";
echo "<tr><th>Requirement</th><th>Status</th></tr>";

foreach ($requirements as $name => $status) {
    if (is_callable($status)) {
        $status = $status();
    }
    $color = $status ? 'green' : 'red';
    $symbol = $status ? '✅ Enabled' : '❌ Missing';
    echo "<tr><td>$name</td><td style='color:$color;font-weight:bold;'>$symbol</td></tr>";
}

echo "</table>";
echo "<p>PHP Version: " . PHP_VERSION . "</p>";
?>
