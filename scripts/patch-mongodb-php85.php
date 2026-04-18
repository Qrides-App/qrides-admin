<?php

declare(strict_types=1);

$baseDir = dirname(__DIR__);
$packageDir = $baseDir . '/vendor/mongodb/mongodb/src';

if (! is_dir($packageDir)) {
    fwrite(STDOUT, "[patch-mongodb-php85] mongodb/mongodb not installed, skipping.\n");
    exit(0);
}

$files = [
    $packageDir . '/functions.php',
    $packageDir . '/GridFS/ReadableStream.php',
    $packageDir . '/Model/IndexInfo.php',
    $packageDir . '/Model/CollectionInfo.php',
    $packageDir . '/Model/DatabaseInfo.php',
    $packageDir . '/Operation/CountDocuments.php',
    $packageDir . '/Operation/Count.php',
];

$patchedFiles = [];

foreach ($files as $file) {
    if (! is_file($file)) {
        continue;
    }

    $contents = file_get_contents($file);

    if ($contents === false) {
        fwrite(STDERR, "[patch-mongodb-php85] Failed reading {$file}.\n");
        exit(1);
    }

    $updated = str_replace('(integer)', '(int)', $contents, $count);

    if ($count === 0) {
        continue;
    }

    if (file_put_contents($file, $updated) === false) {
        fwrite(STDERR, "[patch-mongodb-php85] Failed writing {$file}.\n");
        exit(1);
    }

    $patchedFiles[] = basename($file);
}

if ($patchedFiles === []) {
    fwrite(STDOUT, "[patch-mongodb-php85] No changes needed.\n");
    exit(0);
}

fwrite(
    STDOUT,
    '[patch-mongodb-php85] Patched non-canonical casts in: ' . implode(', ', $patchedFiles) . ".\n"
);
