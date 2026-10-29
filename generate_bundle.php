<?php
/**
 * Script untuk menggabungkan seluruh source code project ke dalam satu file tunggal.
 * Format dioptimalkan untuk LLM / Prompt Context (Gemini, ChatGPT, Claude).
 */

$baseDir = realpath(__DIR__);
$outputMd = $baseDir . DIRECTORY_SEPARATOR . 'all_code_sistem_plts.md';
$outputTxt = $baseDir . DIRECTORY_SEPARATOR . 'all_code_sistem_plts.txt';

$allowedExtensions = ['php', 'js', 'css', 'puml', 'md', 'sql', 'json', 'html'];
$excludePatterns = [
    '/\.git/',
    '/uploads/',
    '/assets[\\\\\/]img/',
    '/generate_bundle\.php$/',
    '/all_code_sistem_plts\.(md|txt)$/'
];

// 1. Recursive scan
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($baseDir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

$files = [];
foreach ($iterator as $item) {
    if (!$item->isFile()) {
        continue;
    }

    $filePath = $item->getRealPath();
    $relPath = str_replace($baseDir . DIRECTORY_SEPARATOR, '', $filePath);
    $relPathNormalized = str_replace('\\', '/', $relPath);

    // Check exclusions
    $excluded = false;
    foreach ($excludePatterns as $pattern) {
        if (preg_match($pattern, $relPathNormalized)) {
            $excluded = true;
            break;
        }
    }
    if ($excluded) {
        continue;
    }

    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    if (in_array($ext, $allowedExtensions)) {
        $files[] = [
            'real' => $filePath,
            'rel'  => $relPathNormalized,
            'ext'  => $ext,
            'size' => $item->getSize(),
        ];
    }
}

// Sort alphabetically by relative path
usort($files, fn($a, $b) => strcmp($a['rel'], $b['rel']));

// 2. Build Output
$out = [];
$out[] = "# REPOSITORY COMPLETE CODEBASE: SISTEM-PLTS";
$out[] = "Generated: " . date('Y-m-d H:i:s');
$out[] = "Total Files Included: " . count($files);
$out[] = "Description: Single-file bundle of the entire codebase for AI Context (Gemini, Claude, GPT).";
$out[] = "";

$out[] = "## 1. PROJECT DIRECTORY TREE";
$out[] = "```text";
foreach ($files as $f) {
    $out[] = sprintf("%-60s (%5.1f KB)", $f['rel'], $f['size'] / 1024);
}
$out[] = "```";
$out[] = "";
$out[] = "---";
$out[] = "";

$out[] = "## 2. SOURCE CODE FILES";
$out[] = "";

$totalLines = 0;

foreach ($files as $idx => $f) {
    $num = $idx + 1;
    $content = file_get_contents($f['real']);
    $lines = substr_count($content, "\n") + 1;
    $totalLines += $lines;

    $langMap = [
        'php' => 'php',
        'js' => 'javascript',
        'css' => 'css',
        'puml' => 'plantuml',
        'md' => 'markdown',
        'sql' => 'sql',
        'json' => 'json',
        'html' => 'html'
    ];
    $codeFence = $langMap[$f['ext']] ?? 'text';

    $out[] = "================================================================================";
    $out[] = "FILE [{$num}/" . count($files) . "]: " . $f['rel'];
    $out[] = "TYPE: " . strtoupper($f['ext']) . " | SIZE: " . number_format($f['size']) . " bytes | LINES: " . $lines;
    $out[] = "================================================================================";
    $out[] = "```" . $codeFence;
    $out[] = $content;
    $out[] = "```";
    $out[] = "";
}

$finalOutput = implode("\n", $out);

// Save as .md
file_put_contents($outputMd, $finalOutput);

// Save as .txt
file_put_contents($outputTxt, $finalOutput);

echo "SUCCESS!\n";
echo "Total files bundled: " . count($files) . "\n";
echo "Total lines of code: " . number_format($totalLines) . "\n";
echo "Output MD file: " . $outputMd . " (" . number_format(filesize($outputMd) / 1024, 2) . " KB)\n";
echo "Output TXT file: " . $outputTxt . " (" . number_format(filesize($outputTxt) / 1024, 2) . " KB)\n";
