<?php
/**
 * audit_security.php - Scan for CSRF and SQL Injection risks
 */

$rootDir = __DIR__;
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($rootDir));
$phpFiles = [];

foreach ($files as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $phpFiles[] = $file->getPathname();
    }
}

echo "--- STARTING SECURITY AUDIT ---\n";

$csrf_risks = [];
$sqli_risks = [];

foreach ($phpFiles as $file) {
    $content = file_get_contents($file);
    $relPath = str_replace($rootDir . DIRECTORY_SEPARATOR, '', $file);

    // 1. CSRF Check: Look for <form method="POST"> WITHOUT csrf_token input
    if (stripos($content, 'method="POST"') !== false || stripos($content, "method='POST'") !== false) {
        // Simple heuristic: if it has POST form but no "csrf_token" string in file, flag it.
        // Better: Check if inside the same file logic.
        if (strpos($content, 'csrf_token') === false) {
            $csrf_risks[] = $relPath;
        }
    }

    // 2. SQL Injection Check: Look for "$pdo->query(" ... $var" or "execute(" ... $var"
    // This is hard to regex perfectly. 
    // We look for: query("SELECT ... $...
    // or: execute("INSERT ... $...

    $lines = explode("\n", $content);
    foreach ($lines as $i => $line) {
        $cleanLine = trim($line);
        // Detect likely unsafe concatenation in query/execute
        if (preg_match('/->(query|exec|prepare)\s*\(\s*["\'].*\$[a-zA-Z_].*["\']\s*\)/i', $cleanLine)) {
            // Exclude false positives like "SELECT * FROM $table" where $table is white-listed (common in backup scripts)
            // But flag everything for review.
            if (strpos($cleanLine, 'backup_bd.php') !== false || strpos($file, 'backup_bd.php') !== false)
                continue; // Skip backup tool which iterates tables

            // Ignore prepared statements usage like: $sql = "SELECT..."; $stmt = $pdo->prepare($sql);
            // The regex catches: ->prepare("SELECT ... $var") which IS unsafe.
            // It also catches: ->query("SELECT ... $var") which IS unsafe.

            $sqli_risks[] = ['file' => $relPath, 'line' => $i + 1, 'code' => substr($cleanLine, 0, 100)];
        }
    }
}

echo "\n--- CSRF RISKS (POST Forms without Token) ---\n";
if (empty($csrf_risks)) {
    echo "✅ No obvious CSRF risks found.\n";
} else {
    foreach ($csrf_risks as $f)
        echo "⚠️  $f\n";
}

echo "\n--- POTENTIAL SQL INJECTION RISKS (Variable interpolation in SQL) ---\n";
if (empty($sqli_risks)) {
    echo "✅ No obvious SQL injection patterns found.\n";
} else {
    foreach ($sqli_risks as $r) {
        echo "⚠️  {$r['file']}:{$r['line']} -> {$r['code']}\n";
    }
}
?>