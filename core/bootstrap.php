<?php
// Outreach AI Freedom - Core Bootstrap & Data Access Layer
declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

// Ensure session starts cleanly
if (session_status() === PHP_SESSION_NONE && PHP_SAPI !== 'cli') {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();
}

$CFG = require __DIR__ . '/config.php';
date_default_timezone_set($CFG['timezone'] ?? 'Asia/Kolkata');

// Constant Definitions
const OUTREACH_VERSION = '1.0.0';

const PIPELINE_STAGES = [
    'new' => [
        'name' => 'New Scraped',
        'color' => 'slate',
        'bg' => 'bg-slate-500/20 text-slate-300 border-slate-500/30',
        'badge' => 'bg-slate-800 text-slate-300',
        'icon' => 'sparkles'
    ],
    'qualified' => [
        'name' => 'Qualified (High Intent)',
        'color' => 'blue',
        'bg' => 'bg-blue-500/20 text-blue-300 border-blue-500/30',
        'badge' => 'bg-blue-900/60 text-blue-300',
        'icon' => 'check-circle'
    ],
    'cold' => [
        'name' => 'Cold Lead',
        'color' => 'cyan',
        'bg' => 'bg-cyan-500/20 text-cyan-300 border-cyan-500/30',
        'badge' => 'bg-cyan-950 text-cyan-300',
        'icon' => 'snowflake'
    ],
    'warm' => [
        'name' => 'Warm Lead',
        'color' => 'amber',
        'bg' => 'bg-amber-500/20 text-amber-300 border-amber-500/30',
        'badge' => 'bg-amber-950 text-amber-300',
        'icon' => 'sun'
    ],
    'hot' => [
        'name' => 'Hot (High Budget)',
        'color' => 'rose',
        'bg' => 'bg-rose-500/20 text-rose-300 border-rose-500/30',
        'badge' => 'bg-rose-950 text-rose-300',
        'icon' => 'flame'
    ],
    'outreach_sent' => [
        'name' => 'Outreach Sent',
        'color' => 'purple',
        'bg' => 'bg-purple-500/20 text-purple-300 border-purple-500/30',
        'badge' => 'bg-purple-950 text-purple-300',
        'icon' => 'send'
    ],
    'followup_1' => [
        'name' => 'Follow-up 1',
        'color' => 'indigo',
        'bg' => 'bg-indigo-500/20 text-indigo-300 border-indigo-500/30',
        'badge' => 'bg-indigo-950 text-indigo-300',
        'icon' => 'rotate-cw'
    ],
    'followup_2' => [
        'name' => 'Follow-up 2',
        'color' => 'violet',
        'bg' => 'bg-violet-500/20 text-violet-300 border-violet-500/30',
        'badge' => 'bg-violet-950 text-violet-300',
        'icon' => 'refresh-cw'
    ],
    'responded' => [
        'name' => 'Responded / In Chat',
        'color' => 'teal',
        'bg' => 'bg-teal-500/20 text-teal-300 border-teal-500/30',
        'badge' => 'bg-teal-950 text-teal-300',
        'icon' => 'message-square'
    ],
    'meeting_booked' => [
        'name' => 'Meeting Booked',
        'color' => 'emerald',
        'bg' => 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30',
        'badge' => 'bg-emerald-950 text-emerald-300',
        'icon' => 'calendar'
    ],
    'closed_won' => [
        'name' => 'Closed Won 🎉',
        'color' => 'green',
        'bg' => 'bg-green-500/20 text-green-300 border-green-500/30',
        'badge' => 'bg-green-950 text-green-300 font-bold',
        'icon' => 'trophy'
    ],
    'closed_lost' => [
        'name' => 'Closed Lost',
        'color' => 'zinc',
        'bg' => 'bg-zinc-700/20 text-zinc-400 border-zinc-700/30',
        'badge' => 'bg-zinc-900 text-zinc-400',
        'icon' => 'x-circle'
    ],
];

// Helper to access configuration keys
function cfg(string $key, $default = null) {
    global $CFG;
    return $CFG[$key] ?? $default;
}

// Global sanitization helper
function h($string): string {
    return htmlspecialchars((string)($string ?? ''), ENT_QUOTES, 'UTF-8');
}

function now(): string {
    return date('Y-m-d H:i:s');
}

function today(): string {
    return date('Y-m-d');
}

/**
 * Universal Database Connection:
 * Tries MySQL first (Hostinger production).
 * Automatically falls back to SQLite if MySQL fails or driver is SQLite.
 */
function db(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $c = cfg('db', []);
    $driver = $c['driver'] ?? 'mysql';

    if ($driver === 'mysql' && !empty($c['host']) && !empty($c['name'])) {
        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $c['host'],
                $c['port'] ?? 3306,
                $c['name'],
                $c['charset'] ?? 'utf8mb4'
            );
            $pdo = new PDO($dsn, $c['user'], $c['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 3,
            ]);
            migrate_database($pdo, 'mysql');
            return $pdo;
        } catch (Throwable $e) {
            error_log('Outreach DB Warning: MySQL connect failed (' . $e->getMessage() . '). Falling back to SQLite.');
        }
    }

    // SQLite Fallback
    $sqlitePath = cfg('sqlite_fallback_path', dirname(__DIR__) . '/data/outreach.sqlite');
    $sqliteDir = dirname($sqlitePath);
    if (!is_dir($sqliteDir)) {
        @mkdir($sqliteDir, 0755, true);
    }

    $pdo = new PDO('sqlite:' . $sqlitePath, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA journal_mode = WAL;');
    $pdo->exec('PRAGMA foreign_keys = ON;');
    migrate_database($pdo, 'sqlite');
    return $pdo;
}

// Database helper functions
function q(string $sql, array $params = []): PDOStatement {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function one(string $sql, array $params = []) {
    $row = q($sql, $params)->fetch();
    return $row ?: null;
}

function all(string $sql, array $params = []): array {
    return q($sql, $params)->fetchAll();
}

function val(string $sql, array $params = []) {
    $column = q($sql, $params)->fetchColumn();
    return $column === false ? null : $column;
}

function last_id(): int {
    return (int)db()->lastInsertId();
}

/**
 * Multi-Tenant Database Migration
 */
function migrate_database(PDO $pdo, string $driver): void {
    static $migrated = false;
    if ($migrated) return;
    $migrated = true;

    if ($driver === 'mysql') {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS settings (
                k VARCHAR(100) PRIMARY KEY,
                v LONGTEXT NOT NULL,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(150) NOT NULL,
                email VARCHAR(191) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                role VARCHAR(30) DEFAULT 'user',
                business_name VARCHAR(255) DEFAULT '',
                business_niche VARCHAR(255) DEFAULT '',
                business_offering TEXT,
                business_pricing VARCHAR(100) DEFAULT '',
                target_icp TEXT,
                usp_hook TEXT,
                sender_name VARCHAR(150) DEFAULT '',
                sender_title VARCHAR(150) DEFAULT '',
                sender_email VARCHAR(191) DEFAULT '',
                sender_phone VARCHAR(50) DEFAULT '',
                sender_whatsapp VARCHAR(50) DEFAULT '',
                linkedin_url VARCHAR(255) DEFAULT '',
                instagram_handle VARCHAR(100) DEFAULT '',
                twitter_handle VARCHAR(100) DEFAULT '',
                default_channel VARCHAR(30) DEFAULT 'whatsapp',
                min_score_filter INT DEFAULT 70,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS leads (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                business_name VARCHAR(255) NOT NULL,
                website VARCHAR(255) DEFAULT '',
                website_status VARCHAR(50) DEFAULT 'verified',
                phone VARCHAR(50) DEFAULT '',
                whatsapp VARCHAR(50) DEFAULT '',
                email VARCHAR(191) DEFAULT '',
                linkedin_url VARCHAR(255) DEFAULT '',
                instagram_url VARCHAR(255) DEFAULT '',
                twitter_url VARCHAR(255) DEFAULT '',
                facebook_url VARCHAR(255) DEFAULT '',
                google_maps_url TEXT,
                address TEXT,
                city VARCHAR(100) DEFAULT '',
                country VARCHAR(100) DEFAULT 'India',
                category VARCHAR(150) DEFAULT '',
                reviews_count INT DEFAULT 0,
                rating DECIMAL(3,2) DEFAULT 0.00,
                paying_capacity_tier VARCHAR(50) DEFAULT 'High / Enterprise',
                qualification_score INT DEFAULT 85,
                is_qualified TINYINT(1) DEFAULT 1,
                qualification_reasons TEXT,
                pipeline_stage VARCHAR(50) DEFAULT 'new',
                deal_value DECIMAL(12,2) DEFAULT 0.00,
                source VARCHAR(50) DEFAULT 'google_places',
                last_contacted_at DATETIME NULL,
                last_channel VARCHAR(30) NULL,
                next_followup_at DATE NULL,
                notes TEXT,
                tags VARCHAR(255) DEFAULT '',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user_stage (user_id, pipeline_stage),
                INDEX idx_score (qualification_score)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS outreach_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                lead_id INT NOT NULL,
                user_id INT NOT NULL,
                channel VARCHAR(30) NOT NULL,
                touch_type VARCHAR(50) NOT NULL,
                message_sent LONGTEXT NOT NULL,
                outcome VARCHAR(50) DEFAULT 'sent',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_lead_history (lead_id, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS connected_channels (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                channel_name VARCHAR(50) NOT NULL,
                account_identifier VARCHAR(255) NOT NULL,
                status VARCHAR(30) DEFAULT 'connected',
                config_data LONGTEXT,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_user_channel (user_id, channel_name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS saved_searches (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                keyword VARCHAR(255) NOT NULL,
                location VARCHAR(255) NOT NULL,
                leads_found INT DEFAULT 0,
                qualified_found INT DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    } else {
        // SQLite Schema
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS settings (
                k TEXT PRIMARY KEY,
                v TEXT NOT NULL,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                password_hash TEXT NOT NULL,
                role TEXT DEFAULT 'user',
                business_name TEXT DEFAULT '',
                business_niche TEXT DEFAULT '',
                business_offering TEXT,
                business_pricing TEXT DEFAULT '',
                target_icp TEXT,
                usp_hook TEXT,
                sender_name TEXT DEFAULT '',
                sender_title TEXT DEFAULT '',
                sender_email TEXT DEFAULT '',
                sender_phone TEXT DEFAULT '',
                sender_whatsapp TEXT DEFAULT '',
                linkedin_url TEXT DEFAULT '',
                instagram_handle TEXT DEFAULT '',
                twitter_handle TEXT DEFAULT '',
                default_channel TEXT DEFAULT 'whatsapp',
                min_score_filter INTEGER DEFAULT 70,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS leads (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                business_name TEXT NOT NULL,
                website TEXT DEFAULT '',
                website_status TEXT DEFAULT 'verified',
                phone TEXT DEFAULT '',
                whatsapp TEXT DEFAULT '',
                email TEXT DEFAULT '',
                linkedin_url TEXT DEFAULT '',
                instagram_url TEXT DEFAULT '',
                twitter_url TEXT DEFAULT '',
                facebook_url TEXT DEFAULT '',
                google_maps_url TEXT,
                address TEXT,
                city TEXT DEFAULT '',
                country TEXT DEFAULT 'India',
                category TEXT DEFAULT '',
                reviews_count INTEGER DEFAULT 0,
                rating REAL DEFAULT 0.00,
                paying_capacity_tier TEXT DEFAULT 'High / Enterprise',
                qualification_score INTEGER DEFAULT 85,
                is_qualified INTEGER DEFAULT 1,
                qualification_reasons TEXT,
                pipeline_stage TEXT DEFAULT 'new',
                deal_value REAL DEFAULT 0.00,
                source TEXT DEFAULT 'google_places',
                last_contacted_at DATETIME NULL,
                last_channel TEXT NULL,
                next_followup_at DATE NULL,
                notes TEXT,
                tags TEXT DEFAULT '',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS outreach_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                lead_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                channel TEXT NOT NULL,
                touch_type TEXT NOT NULL,
                message_sent TEXT NOT NULL,
                outcome TEXT DEFAULT 'sent',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS connected_channels (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                channel_name TEXT NOT NULL,
                account_identifier TEXT NOT NULL,
                status TEXT DEFAULT 'connected',
                config_data TEXT,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(user_id, channel_name)
            );

            CREATE TABLE IF NOT EXISTS saved_searches (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                keyword TEXT NOT NULL,
                location TEXT NOT NULL,
                leads_found INTEGER DEFAULT 0,
                qualified_found INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        ");
    }

    seed_default_admin($pdo);
}

/**
 * Seeds default owner / admin account if empty
 */
function seed_default_admin(PDO $pdo): void {
    try {
        $count = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        if ($count === 0) {
            $stmt = $pdo->prepare("
                INSERT INTO users (
                    name, email, password_hash, role,
                    business_name, business_niche, business_offering,
                    business_pricing, target_icp, usp_hook,
                    sender_name, sender_title, sender_email, sender_phone, sender_whatsapp,
                    linkedin_url, instagram_handle, default_channel, min_score_filter, created_at
                ) VALUES (
                    ?, ?, ?, 'owner',
                    ?, ?, ?,
                    ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, 'whatsapp', 75, ?
                )
            ");
            $now = now();
            $stmt->execute([
                'The Only AI Freedom Outreach Lead',
                'outreach@theonlyaifreedom.in',
                password_hash('Outreach_Freedom_2026!', PASSWORD_DEFAULT),
                'The Only AI Freedom',
                'AI Solutions & Automation Consulting',
                'We deliver tailored, high-ROI AI agents, CRM workflows, and WhatsApp marketing engines that convert 3x more qualified leads for high-ticket commercial businesses.',
                '₹49,999 - ₹1,50,000 / project',
                'High-end Jewellery Brands, B2B SaaS, Real Estate Developers, Luxury Architects, and Multi-speciality Clinics.',
                'We guarantee a working AI lead capture & conversion pilot live in 7 days or full money back.',
                'Rajjat Rangari',
                'Founder & Automation Architect',
                'outreach@theonlyaifreedom.in',
                '+917620654814',
                '+917620654814',
                'https://linkedin.com/company/the-only-ai-freedom',
                '@theonlyaifreedom',
                $now
            ]);
        }
    } catch (Throwable $e) {
        // Ignored if table is locked during parallel init
    }
}

// Authentication Helpers
function current_user(): ?array {
    if (empty($_SESSION['outreach_user_id'])) {
        return null;
    }
    static $userCache = null;
    if ($userCache !== null && $userCache['id'] == $_SESSION['outreach_user_id']) {
        return $userCache;
    }
    $user = one("SELECT * FROM users WHERE id = ?", [$_SESSION['outreach_user_id']]);
    if (!$user) {
        unset($_SESSION['outreach_user_id']);
        return null;
    }
    $userCache = $user;
    return $user;
}

function require_auth(): array {
    $u = current_user();
    if (!$u) {
        $target = urlencode($_SERVER['REQUEST_URI'] ?? '/');
        header('Location: /login.php?redirect=' . $target);
        exit;
    }
    return $u;
}

function json_response(array $data, int $status = 200): void {
    if (!headers_sent()) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
