<?php
if (PHP_SAPI !== 'cli') exit(1);
$webroot = realpath($argv[1] ?? dirname(__DIR__));
$count = 0;
$connections = array();
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($webroot, FilesystemIterator::SKIP_DOTS)) as $file) {
    if (!preg_match('~^(php|beheer|jota)/~', substr($file->getPathname(), strlen($webroot) + 1)) || $file->getExtension() !== 'php' || strpos($file->getPathname(), '/.svn/') !== false) continue;
    $count++;
    $code = '';
    foreach (token_get_all(file_get_contents($file->getPathname()), TOKEN_PARSE) as $token) {
        if (is_array($token) && in_array($token[0], array(T_COMMENT, T_DOC_COMMENT), true)) continue;
        $code .= is_array($token) ? $token[1] : $token;
    }
    if (strpos($file->getPathname(), '/subm/') !== false) continue;
    if (preg_match('/\bnew\s+mysqli\s*\(/i', $code)) $connections[] = substr($file->getPathname(), strlen($webroot) + 1);
    if (preg_match('/\b(databaseMYSQLi|beheerConnectRaw|BeheerDatabase)\b/', $code)) throw new RuntimeException('Old database API retained: '.$file->getFilename());
    preg_match_all('~\b(?:require_once|require|include_once|include)\s*(?:\(\s*)?(?:__DIR__\s*\.\s*)?[\x22\x27]([^\x22\x27]+\.php)[\x22\x27]~', $code, $matches);
    foreach ($matches[1] as $relative) {
        if (!is_file($file->getPath().'/'.ltrim($relative, '/'))) throw new RuntimeException('Missing include in '.substr($file->getPathname(),strlen($webroot)+1).': '.$relative);
    }
}
if ($connections !== array('php/database.php')) throw new RuntimeException('Database connection is not centralized');
echo $count." PHP files: syntax, static includes, retired API removal and single database connection PASS\n";
