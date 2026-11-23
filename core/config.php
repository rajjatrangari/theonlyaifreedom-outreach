<?php
// Outreach AI Freedom - Core Configuration
declare(strict_types=1);

$localConfig = __DIR__ . '/config.local.php';
if (file_exists($localConfig)) {
    return require $localConfig;
}

return [
    'app_name' => 'Outreach AI Freedom',
    'app_url' => 'https://outreach.theonlyaifreedom.in',
    'timezone' => 'Asia/Kolkata',
    'secret' => 'toaf-outreach-enterprise-secret-key-2026-isolated',
    'version' => '1.0.0',
    'min_qualification_score' => 70, // Filter out non-qualified leads below 70

    // Database Configuration
    // Primary: Hostinger Dedicated MySQL Database
    'db' => [
        'driver' => 'mysql',
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'u673048014_outreach',
        'user' => 'u673048014_outreach',
        'pass' => 'Outreach_2026!Sec#Db7',
        'charset' => 'utf8mb4',
    ],

    // Fallback: SQLite storage in data/ for zero-friction portability
    'sqlite_fallback_path' => dirname(__DIR__) . '/data/outreach.sqlite',

    // Default AI Engine Settings
    'ai' => [
        'provider' => 'builtin', // builtin high-converting engine + API hook
        'api_key' => getenv('OPENAI_API_KEY') ?: (getenv('ANTHROPIC_API_KEY') ?: ''),
        'model' => 'gpt-4o-mini',
    ],
];
