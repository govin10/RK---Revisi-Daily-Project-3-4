<?php
$files = glob(__DIR__ . '/alumni/*.php');
foreach ($files as $file) {
    $content = file_get_contents($file);
    // Replace htmlspecialchars($alumni['...']) with htmlspecialchars($alumni['...'] ?? '')
    // Regex: htmlspecialchars\(\$([a-zA-Z0-9_]+)\['([a-zA-Z0-9_]+)'\]\)
    $content = preg_replace('/htmlspecialchars\(\$([a-zA-Z0-9_]+)\[\'([a-zA-Z0-9_]+)\'\]\)/', 'htmlspecialchars($$1[\'$2\'] ?? \'\')', $content);
    
    // Also handle $search variable in index.php
    $content = preg_replace('/htmlspecialchars\(\$search\)/', 'htmlspecialchars($search ?? \'\')', $content);
    
    file_put_contents($file, $content);
}
echo "Fixed null deprecations.";
