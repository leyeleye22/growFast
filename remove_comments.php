<?php

$baseDir = __DIR__;
$appDir = $baseDir . '/app';
$routesDir = $baseDir . '/routes';

$files = [];
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($appDir)) as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $files[] = $file->getPathname();
    }
}
$files[] = $routesDir . '/api.php';
$files[] = $routesDir . '/web.php';

$modified = [];
foreach ($files as $path) {
    if (!file_exists($path)) continue;
    $code = file_get_contents($path);
    $newCode = removePhpComments($code);
    if ($newCode !== $code) {
        file_put_contents($path, $newCode);
        $modified[] = str_replace($baseDir . DIRECTORY_SEPARATOR, '', $path);
    }
}

echo implode("\n", $modified);

function removePhpComments(string $code): string {
    $tokens = @token_get_all($code);
    if (empty($tokens)) return $code;

    $output = '';
    foreach ($tokens as $token) {
        if (is_array($token)) {
            [$id, $text] = $token;
            if ($id === T_COMMENT || $id === T_DOC_COMMENT) {
                if (str_contains($text, "\n")) {
                    $output .= "\n";
                }
                continue;
            }
        }
        $output .= is_array($token) ? $token[1] : $token;
    }
    return $output;
}
