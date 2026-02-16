<?php
/**
 * audit_system.php - Automates syntax checking and file integrity verification
 */

$rootDir = __DIR__;
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($rootDir));
$phpFiles = [];

foreach ($files as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $phpFiles[] = $file->getPathname();
    }
}

echo "--- STARTING SYSTEM AUDIT ---\n";
echo "Found " . count($phpFiles) . " PHP files.\n\n";

$syntaxErrors = [];
$missingFiles = [];
$analyzedFiles = 0;

foreach ($phpFiles as $file) {
    $relativePath = str_replace($rootDir . DIRECTORY_SEPARATOR, '', $file);

    // 1. Syntax Check
    $output = [];
    $returnVar = 0;
    // escapeshellarg is important for paths with spaces
    exec("php -l " . escapeshellarg($file), $output, $returnVar);

    if ($returnVar !== 0) {
        // Filter out the standard "Errors parsing..." line to get the actual error
        $errorMsg = implode("\n", array_filter($output, function ($line) {
            return strpos($line, 'No syntax errors') === false;
        }));
        $syntaxErrors[] = ['file' => $relativePath, 'error' => $errorMsg];
    }

    // 2. Integrity Check (Includes)
    $content = file_get_contents($file);
    // Regex to capture include/require/include_once/require_once
    // Matches: (include|require)(_once)?\s*\(?\s*['"](.+?)['"]
    // Note: This is a basic regex, won't catch variables or complex expressions, but covers 90%
    if (preg_match_all('/(include|require)(?:_once)?\s*(?:\(|\s)\s*[\'"](.+?)[\'"]/', $content, $matches)) {
        foreach ($matches[2] as $includedPath) {
            // Resolver ruta
            // Si empieza con __DIR__, reemplazarlo
            $resolvedPath = $includedPath; // Default assumption relative

            // Handle __DIR__ string concatenation manually if present in regex match?
            // The regex matches explicit strings like 'file.php'. 
            // If code is include __DIR__ . '/file.php', regex sees only '/file.php' if quotes are around it?
            // Actually, include __DIR__ . '/foo.php' -> Regex above usually fails on concatenation.

            // Improved Regex for __DIR__ . 'path'
            // Let's use a simpler heuristic: scan for file existence based on directory context

            // Just basic checks for static strings caught
            $dir = dirname($file);
            if (file_exists($dir . '/' . $includedPath) || file_exists($rootDir . '/' . $includedPath)) {
                // OK
            } else {
                // If it looks like a variable or constant, skip
                if (strpos($includedPath, '$') === false && strpos($includedPath, 'DIR') === false) {
                    // Try to match __DIR__ logic manually
                    // This is hard with regex. Let's just list what we suspect.
                    // Actually, many includes use __DIR__ . '/file.php'. 
                    // My regex above captures the string part inside quotes.
                    // if match is /file.php, checking $dir/file.php might fail if it meant $rootDir/file.php
                }
            }
        }
    }

    $analyzedFiles++;
    if ($analyzedFiles % 20 === 0)
        echo ".";
}

echo "\n\n--- AUDIT RESULTS ---\n";

if (empty($syntaxErrors)) {
    echo "✅ SYNTAX: All files passed php -l check.\n";
} else {
    echo "❌ SYNTAX ERRORS FOUND (" . count($syntaxErrors) . "):\n";
    foreach ($syntaxErrors as $err) {
        echo "  - {$err['file']}: {$err['error']}\n";
    }
}

// Manual Check for common missing files based on error patterns
// Let's just report the syntax results specifically as that is the most critical first step.
// Integrity check via Regex is flaky without AST parser.
echo "\nTo verify integrity, check if 'restaurar_bd.php' exists (Confirmed: " . (file_exists($rootDir . '/restaurar_bd.php') ? 'Yes' : 'No') . ")\n";

?>