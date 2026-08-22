<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

$defaults = [
    'hero_title' => 'Telecom Engineering, 5G & Quality Assurance: Key Trends and Opportunities',
    'hero_description' => 'Telecom engineer with 3+ years of experience in telecom infrastructure, hardware installation assurance, 5G deployment, RF technologies and site quality control.',
    'hero_byline' => 'By Amine Janah • August 11, 2026 • 5 min read',
    'homepage_video' => 'media/homepage-video-poster.mp4',
    'homepage_poster' => '',
    'homepage_video_caption' => '1st SBC training for 5G installation',
];

try {
    $pdo->exec('CREATE TABLE IF NOT EXISTS site_settings (setting_key VARCHAR(100) PRIMARY KEY, setting_value TEXT NULL, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB');
    $rows = $pdo->query('SELECT setting_key, setting_value FROM site_settings')->fetchAll();
    foreach ($rows as $row) {
        if (array_key_exists($row['setting_key'], $defaults) && $row['setting_value'] !== null && $row['setting_value'] !== '') {
            $defaults[$row['setting_key']] = $row['setting_value'];
        }
    }
    echo json_encode(['success' => true, 'settings' => $defaults]);
} catch (PDOException $exception) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Unable to load site settings.']);
}