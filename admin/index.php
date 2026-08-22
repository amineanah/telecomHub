<?php
session_start();

const DEFAULT_ADMIN_PASSWORD = 'admin123';

function escapeHtml($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function csrfToken() {
    if (empty($_SESSION['admin_csrf'])) {
        $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['admin_csrf'];
}

function redirectToTab($tab, $message = '') {
    $url = 'index.php?tab=' . rawurlencode($tab);
    if ($message !== '') {
        $url .= '&message=' . rawurlencode($message);
    }
    header('Location: ' . $url);
    exit;
}

$configuredPassword = getenv('TELECOMHUB_ADMIN_PASSWORD') ?: DEFAULT_ADMIN_PASSWORD;

if (($_POST['action'] ?? '') === 'login') {
    if (hash_equals($configuredPassword, (string) ($_POST['password'] ?? ''))) {
        session_regenerate_id(true);
        $_SESSION['is_admin'] = true;
        csrfToken();
        redirectToTab('dashboard');
    }
    $loginError = 'Incorrect password.';
}

if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: index.php');
    exit;
}

if (empty($_SESSION['is_admin'])):
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TelecomHub Admin</title>
    <style>
        * { box-sizing: border-box; }
        body { min-height: 100vh; margin: 0; display: grid; place-items: center; padding: 24px; background: #eaf2f8; color: #16233a; font-family: Georgia, 'Times New Roman', serif; }
        main { width: min(100%, 430px); padding: 42px; background: #fffdf8; border: 1px solid #d4e1eb; box-shadow: 10px 10px 0 #173f5f; }
        .mark { color: #007d82; font: 700 13px/1.2 Arial, sans-serif; letter-spacing: 1.4px; text-transform: uppercase; }
        h1 { margin: 12px 0 8px; font-size: 36px; }
        p { color: #607087; line-height: 1.55; }
        label { display: block; margin-top: 28px; font: 700 13px/1.2 Arial, sans-serif; letter-spacing: .5px; text-transform: uppercase; }
        input { width: 100%; margin-top: 9px; padding: 13px; border: 1px solid #9eb4c7; background: #fff; font-size: 16px; }
        button { width: 100%; margin-top: 20px; padding: 14px; border: 0; background: #007d82; color: white; cursor: pointer; font: 700 14px/1 Arial, sans-serif; letter-spacing: .5px; text-transform: uppercase; }
        .error { margin-top: 18px; padding: 11px; background: #fde8e3; color: #9b2c2c; font-family: Arial, sans-serif; }
    </style>
</head>
<body>
    <main>
        <div class="mark">TelecomHub / Administration</div>
        <h1>Control room</h1>
        <p>Manage the content and visitor activity across your website.</p>
        <?php if (!empty($loginError)): ?><div class="error"><?= escapeHtml($loginError) ?></div><?php endif; ?>
        <form method="post">
            <input type="hidden" name="action" value="login">
            <label for="password">Admin password</label>
            <input id="password" name="password" type="password" required autofocus>
            <button type="submit">Sign in</button>
        </form>
    </main>
</body>
</html>
<?php
exit;
endif;

require_once __DIR__ . '/../db.php';

$pdo->exec('CREATE TABLE IF NOT EXISTS site_settings (setting_key VARCHAR(100) PRIMARY KEY, setting_value TEXT NULL, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB');

$siteSettingDefaults = [
    'hero_title' => 'Telecom Engineering, 5G & Quality Assurance: Key Trends and Opportunities',
    'hero_description' => 'Telecom engineer with 3+ years of experience in telecom infrastructure, hardware installation assurance, 5G deployment, RF technologies and site quality control.',
    'hero_byline' => 'By Amine Janah • August 11, 2026 • 5 min read',
    'homepage_video' => 'media/homepage-video-poster.mp4',
    'homepage_poster' => '',
    'homepage_video_caption' => '1st SBC training for 5G installation',
];

function currentSiteSettings($pdo, $defaults) {
    $settings = $defaults;
    foreach ($pdo->query('SELECT setting_key, setting_value FROM site_settings')->fetchAll() as $row) {
        if (array_key_exists($row['setting_key'], $settings) && $row['setting_value'] !== null && $row['setting_value'] !== '') {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    return $settings;
}

$tabs = ['dashboard', 'homepage', 'media', 'articles', 'categories', 'feedback', 'subscribers', 'jobs'];
$tab = $_GET['tab'] ?? 'dashboard';
if (!in_array($tab, $tabs, true)) {
    $tab = 'dashboard';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') !== 'login') {
    if (!hash_equals(csrfToken(), (string) ($_POST['csrf'] ?? ''))) {
        http_response_code(403);
        exit('Invalid form token. Please refresh the page and try again.');
    }

    $action = $_POST['action'] ?? '';
    $returnTab = $_POST['return_tab'] ?? $tab;

    if ($action === 'save_article') {
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $articleId = (int) ($_POST['article_id'] ?? 0);
        $status = $_POST['status'] ?? 'draft';
        if ($title === '' || $content === '' || !in_array($status, ['draft', 'published', 'archived'], true)) {
            redirectToTab('articles', 'A title, content, and valid status are required.');
        }
        $slug = trim($_POST['slug'] ?? '');
        if ($slug === '') {
            $slug = trim(strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', $title)), '-');
        }
        $fields = [
            ':title' => $title,
            ':slug' => $slug,
            ':excerpt' => trim($_POST['excerpt'] ?? ''),
            ':content' => $content,
            ':image_url' => trim($_POST['image_url'] ?? '') ?: null,
            ':category_id' => ($_POST['category_id'] ?? '') !== '' ? (int) $_POST['category_id'] : null,
            ':is_featured' => isset($_POST['is_featured']) ? 1 : 0,
            ':status' => $status,
            ':published_at' => $status === 'published' ? date('Y-m-d H:i:s') : null,
        ];
        try {
            if ($articleId > 0) {
                $fields[':id'] = $articleId;
                $pdo->prepare('UPDATE articles SET title = :title, slug = :slug, excerpt = :excerpt, content = :content, image_url = :image_url, category_id = :category_id, is_featured = :is_featured, status = :status, published_at = CASE WHEN :status = "published" THEN COALESCE(published_at, :published_at) ELSE NULL END WHERE id = :id')->execute($fields);
                redirectToTab('articles', 'Article updated.');
            }
            $pdo->prepare('INSERT INTO articles (title, slug, excerpt, content, image_url, category_id, is_featured, status, published_at) VALUES (:title, :slug, :excerpt, :content, :image_url, :category_id, :is_featured, :status, :published_at)')->execute($fields);
            redirectToTab('articles', 'Article created.');
        } catch (PDOException $exception) {
            redirectToTab('articles', 'The article could not be saved. Check that its slug is unique.');
        }
    }

    if ($action === 'delete_article') {
        $pdo->prepare('DELETE FROM articles WHERE id = :id')->execute([':id' => (int) ($_POST['id'] ?? 0)]);
        redirectToTab('articles', 'Article deleted.');
    }

    if ($action === 'save_category') {
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        if ($name === '') {
            redirectToTab('categories', 'A category name is required.');
        }
        if ($slug === '') {
            $slug = trim(strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', $name)), '-');
        }
        try {
            $fields = [
                ':name' => $name,
                ':slug' => $slug,
                ':icon' => trim($_POST['icon'] ?? '') ?: null,
                ':description' => trim($_POST['description'] ?? '') ?: null,
            ];
            if ($categoryId > 0) {
                $fields[':id'] = $categoryId;
                $pdo->prepare('UPDATE categories SET name = :name, slug = :slug, icon = :icon, description = :description WHERE id = :id')->execute($fields);
                redirectToTab('categories', 'Category updated.');
            }
            $pdo->prepare('INSERT INTO categories (name, slug, icon, description) VALUES (:name, :slug, :icon, :description)')->execute($fields);
            redirectToTab('categories', 'Category created.');
        } catch (PDOException $exception) {
            redirectToTab('categories', 'The category could not be saved. Check that its slug is unique.');
        }
    }

    if ($action === 'delete_category') {
        $pdo->prepare('DELETE FROM categories WHERE id = :id')->execute([':id' => (int) ($_POST['id'] ?? 0)]);
        redirectToTab('categories', 'Category deleted. Existing articles have been left uncategorised.');
    }

    if ($action === 'save_homepage') {
        $settings = [
            'hero_title' => trim($_POST['hero_title'] ?? ''),
            'hero_description' => trim($_POST['hero_description'] ?? ''),
            'hero_byline' => trim($_POST['hero_byline'] ?? ''),
            'homepage_video' => trim($_POST['homepage_video'] ?? ''),
            'homepage_poster' => trim($_POST['homepage_poster'] ?? ''),
            'homepage_video_caption' => trim($_POST['homepage_video_caption'] ?? ''),
        ];
        foreach ($settings as $key => $value) {
            $pdo->prepare('INSERT INTO site_settings (setting_key, setting_value) VALUES (:key, :value) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)')->execute([':key' => $key, ':value' => $value]);
        }
        redirectToTab('homepage', 'Homepage content saved. Refresh the public homepage to see it.');
    }

    if ($action === 'upload_media') {
        if (empty($_FILES['media_file']) || $_FILES['media_file']['error'] !== UPLOAD_ERR_OK) {
            redirectToTab('media', 'Choose a file to upload.');
        }
        $file = $_FILES['media_file'];
        if ($file['size'] > 100 * 1024 * 1024) {
            redirectToTab('media', 'The file is larger than the 100 MB upload limit.');
        }
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'mp4', 'webm'];
        if (!in_array($extension, $allowedExtensions, true)) {
            redirectToTab('media', 'Use JPG, PNG, WebP, GIF, MP4, or WebM media files.');
        }
        $uploadDirectory = __DIR__ . '/../media/uploads';
        if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true)) {
            redirectToTab('media', 'The media upload folder could not be created.');
        }
        $fileName = date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
        if (!move_uploaded_file($file['tmp_name'], $uploadDirectory . DIRECTORY_SEPARATOR . $fileName)) {
            redirectToTab('media', 'The upload could not be saved.');
        }
        redirectToTab('media', 'Media uploaded: media/uploads/' . $fileName);
    }

    if ($action === 'feedback_status') {
        $status = $_POST['status'] ?? '';
        if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $pdo->prepare('UPDATE feedback SET status = :status WHERE id = :id')->execute([':status' => $status, ':id' => (int) ($_POST['id'] ?? 0)]);
        }
        redirectToTab('feedback', 'Feedback updated.');
    }

    if ($action === 'delete_feedback') {
        $pdo->prepare('DELETE FROM feedback WHERE id = :id')->execute([':id' => (int) ($_POST['id'] ?? 0)]);
        redirectToTab('feedback', 'Feedback deleted.');
    }

    if ($action === 'subscriber_status') {
        $isActive = (int) ($_POST['is_active'] ?? 0) === 1 ? 1 : 0;
        $pdo->prepare('UPDATE newsletter_subscribers SET is_active = :active, unsubscribed_at = CASE WHEN :active = 0 THEN NOW() ELSE NULL END WHERE id = :id')->execute([':active' => $isActive, ':id' => (int) ($_POST['id'] ?? 0)]);
        redirectToTab('subscribers', $isActive ? 'Subscriber reactivated.' : 'Subscriber deactivated.');
    }

    if ($action === 'job_status') {
        $status = $_POST['status'] === 'closed' ? 'closed' : 'open';
        $pdo->prepare('UPDATE jobs SET status = :status WHERE id = :id')->execute([':status' => $status, ':id' => (int) ($_POST['id'] ?? 0)]);
        redirectToTab('jobs', 'Job status updated.');
    }

    if ($action === 'delete_job') {
        $pdo->prepare('DELETE FROM jobs WHERE id = :id')->execute([':id' => (int) ($_POST['id'] ?? 0)]);
        redirectToTab('jobs', 'Job deleted.');
    }

    redirectToTab($returnTab);
}

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$siteSettings = currentSiteSettings($pdo, $siteSettingDefaults);
$metrics = [
    'published_articles' => (int) $pdo->query("SELECT COUNT(*) FROM articles WHERE status = 'published'")->fetchColumn(),
    'pending_feedback' => (int) $pdo->query("SELECT COUNT(*) FROM feedback WHERE status = 'pending'")->fetchColumn(),
    'active_subscribers' => (int) $pdo->query('SELECT COUNT(*) FROM newsletter_subscribers WHERE is_active = 1')->fetchColumn(),
    'open_jobs' => (int) $pdo->query("SELECT COUNT(*) FROM jobs WHERE status = 'open'")->fetchColumn(),
];
$articleToEdit = null;
if ($tab === 'articles' && isset($_GET['edit'])) {
    $statement = $pdo->prepare('SELECT * FROM articles WHERE id = :id');
    $statement->execute([':id' => (int) $_GET['edit']]);
    $articleToEdit = $statement->fetch();
}
$categoryToEdit = null;
if ($tab === 'categories' && isset($_GET['edit'])) {
    $statement = $pdo->prepare('SELECT * FROM categories WHERE id = :id');
    $statement->execute([':id' => (int) $_GET['edit']]);
    $categoryToEdit = $statement->fetch();
}
$categoryItems = $tab === 'categories' ? $pdo->query('SELECT c.*, COUNT(a.id) AS article_count FROM categories c LEFT JOIN articles a ON a.category_id = c.id GROUP BY c.id ORDER BY c.name')->fetchAll() : [];
$articles = $tab === 'articles' ? $pdo->query('SELECT a.*, c.name AS category_name FROM articles a LEFT JOIN categories c ON c.id = a.category_id ORDER BY a.updated_at DESC LIMIT 50')->fetchAll() : [];
$feedbackItems = $tab === 'feedback' ? $pdo->query('SELECT * FROM feedback ORDER BY created_at DESC LIMIT 100')->fetchAll() : [];
$subscribers = $tab === 'subscribers' ? $pdo->query('SELECT * FROM newsletter_subscribers ORDER BY subscribed_at DESC LIMIT 100')->fetchAll() : [];
$jobs = $tab === 'jobs' ? $pdo->query('SELECT j.*, c.name AS company_name FROM jobs j JOIN companies c ON c.id = j.company_id ORDER BY j.posted_at DESC LIMIT 100')->fetchAll() : [];
$mediaFiles = [];
if ($tab === 'media' || $tab === 'homepage') {
    $mediaDirectory = __DIR__ . '/../media';
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($mediaDirectory, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if ($file->isFile() && in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png', 'webp', 'gif', 'mp4', 'webm'], true)) {
            $mediaFiles[] = ['path' => 'media/' . str_replace('\\', '/', substr($file->getPathname(), strlen($mediaDirectory) + 1)), 'size' => $file->getSize(), 'extension' => strtolower($file->getExtension())];
        }
    }
    usort($mediaFiles, function ($left, $right) { return strcmp($right['path'], $left['path']); });
}
$recentArticles = $pdo->query('SELECT id, title, status, updated_at FROM articles ORDER BY updated_at DESC LIMIT 5')->fetchAll();
$recentFeedback = $pdo->query('SELECT id, name, message, status, created_at FROM feedback ORDER BY created_at DESC LIMIT 5')->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TelecomHub Admin</title>
    <style>
        :root { --ink: #16233a; --muted: #64748b; --line: #dce5ed; --paper: #fffdf8; --canvas: #eef4f7; --navy: #173f5f; --teal: #007d82; --amber: #d9822b; --red: #bc3f42; --green: #287d55; }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--canvas); color: var(--ink); font-family: Arial, Helvetica, sans-serif; }
        .shell { display: grid; grid-template-columns: 244px minmax(0, 1fr); min-height: 100vh; }
        aside { padding: 28px 18px; background: var(--navy); color: #fff; }
        .brand { margin: 0 0 42px; font: 700 25px Georgia, serif; }
        .brand span { color: #84d7cd; }
        .brand small { display: block; margin-top: 6px; color: #bdcedc; font: 700 10px Arial, sans-serif; letter-spacing: 1.3px; text-transform: uppercase; }
        nav a { display: block; margin: 5px 0; padding: 12px; color: #d5e3ec; text-decoration: none; font-size: 14px; font-weight: 700; }
        nav a:hover, nav a.active { background: #255979; color: white; }
        .back { display: block; margin-top: 44px; padding: 12px; border: 1px solid #7091a8; color: white; text-align: center; text-decoration: none; font-size: 13px; }
        main { padding: 30px clamp(20px, 4vw, 58px) 55px; overflow: hidden; }
        .top { display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; margin-bottom: 28px; }
        .eyebrow { margin: 0 0 6px; color: var(--teal); font-size: 11px; font-weight: 700; letter-spacing: 1.4px; text-transform: uppercase; }
        h1 { margin: 0; font: 700 clamp(30px, 4vw, 42px)/1.05 Georgia, serif; }
        .logout { color: var(--navy); font-size: 13px; font-weight: 700; }
        .notice { margin: 0 0 22px; padding: 13px 15px; border-left: 4px solid var(--teal); background: #ddf4ef; color: #125c56; }
        .stats { display: grid; grid-template-columns: repeat(4, minmax(130px, 1fr)); gap: 14px; margin-bottom: 26px; }
        .stat { min-height: 132px; padding: 18px; background: var(--paper); border: 1px solid var(--line); border-top: 4px solid var(--teal); }
        .stat:nth-child(2) { border-top-color: var(--amber); } .stat:nth-child(3) { border-top-color: var(--navy); } .stat:nth-child(4) { border-top-color: var(--green); }
        .stat strong { display: block; margin-top: 18px; font: 700 38px/1 Georgia, serif; }
        .stat span { color: var(--muted); font-size: 12px; font-weight: 700; text-transform: uppercase; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 20px; }
        .panel { margin-bottom: 20px; background: var(--paper); border: 1px solid var(--line); }
        .panel-head { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 17px 19px; border-bottom: 1px solid var(--line); }
        h2 { margin: 0; font: 700 21px Georgia, serif; }
        .panel-body { padding: 20px; }
        .list-item { display: flex; justify-content: space-between; gap: 12px; padding: 13px 0; border-bottom: 1px solid var(--line); font-size: 14px; }
        .list-item:last-child { border-bottom: 0; } .list-item strong { display: block; margin-bottom: 4px; } .subtle { color: var(--muted); font-size: 12px; }
        .tag { display: inline-block; padding: 4px 7px; background: #e6eef3; color: #405267; font-size: 10px; font-weight: 700; letter-spacing: .5px; text-transform: uppercase; white-space: nowrap; }
        .tag.approved, .tag.published, .tag.open { background: #dff2e7; color: #16613f; } .tag.pending, .tag.draft { background: #fff0d7; color: #915a14; } .tag.rejected, .tag.archived, .tag.closed { background: #f9dfdf; color: #973334; }
        .button { display: inline-block; padding: 10px 13px; border: 0; background: var(--teal); color: white; cursor: pointer; font-size: 12px; font-weight: 700; text-decoration: none; }
        .button.secondary { background: var(--navy); } .button.danger { background: var(--red); } .button.light { background: #dfe9ee; color: var(--navy); }
        .inline-form { display: inline; } .actions { display: flex; flex-wrap: wrap; gap: 6px; }
        form label { display: block; margin: 15px 0 6px; color: #405267; font-size: 12px; font-weight: 700; }
        input, textarea, select { width: 100%; padding: 10px; border: 1px solid #aec0cf; background: white; color: var(--ink); font: inherit; }
        textarea { min-height: 180px; resize: vertical; } .form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0 16px; }
        .checkbox { display: flex; align-items: center; gap: 8px; margin: 18px 0; font-size: 13px; font-weight: 700; } .checkbox input { width: auto; }
        .table-wrap { overflow-x: auto; } table { width: 100%; border-collapse: collapse; font-size: 13px; } th { padding: 11px 13px; background: #e8f0f4; color: #405267; text-align: left; font-size: 11px; letter-spacing: .6px; text-transform: uppercase; } td { padding: 13px; border-bottom: 1px solid var(--line); vertical-align: top; } .message { max-width: 330px; color: #4a5a6d; line-height: 1.45; }
        @media (max-width: 900px) { .shell { grid-template-columns: 1fr; } aside { padding: 18px; } .brand { margin-bottom: 15px; } nav { display: flex; overflow-x: auto; gap: 4px; } nav a { white-space: nowrap; } .back { display: none; } .stats { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 620px) { main { padding: 24px 16px 38px; } .top, .grid { display: block; } .top > * { margin-bottom: 15px; } .stats, .form-grid { grid-template-columns: 1fr; } .panel-body { padding: 15px; } }
    </style>
</head>
<body>
<div class="shell">
    <aside>
        <div class="brand">Telecom<span>Hub</span><small>Administration</small></div>
        <nav>
            <?php foreach ($tabs as $navTab): ?><a class="<?= $tab === $navTab ? 'active' : '' ?>" href="?tab=<?= $navTab ?>"><?= escapeHtml(ucfirst($navTab)) ?></a><?php endforeach; ?>
        </nav>
        <a class="back" href="../Html%20code.html">View public site</a>
    </aside>
    <main>
        <div class="top"><div><p class="eyebrow">TelecomHub control panel</p><h1><?= escapeHtml(ucfirst($tab)) ?></h1></div><a class="logout" href="?logout=1">Sign out</a></div>
        <?php if (!empty($_GET['message'])): ?><div class="notice"><?= escapeHtml($_GET['message']) ?></div><?php endif; ?>
        <section class="stats">
            <div class="stat"><span>Published articles</span><strong><?= $metrics['published_articles'] ?></strong></div>
            <div class="stat"><span>Feedback to review</span><strong><?= $metrics['pending_feedback'] ?></strong></div>
            <div class="stat"><span>Active subscribers</span><strong><?= $metrics['active_subscribers'] ?></strong></div>
            <div class="stat"><span>Open jobs</span><strong><?= $metrics['open_jobs'] ?></strong></div>
        </section>

        <?php if ($tab === 'dashboard'): ?>
            <div class="grid">
                <section class="panel"><div class="panel-head"><h2>Recent articles</h2><a class="button" href="?tab=articles">Manage</a></div><div class="panel-body">
                    <?php foreach ($recentArticles as $article): ?><div class="list-item"><div><strong><?= escapeHtml($article['title']) ?></strong><span class="subtle">Updated <?= escapeHtml(date('M j, Y', strtotime($article['updated_at']))) ?></span></div><span class="tag <?= escapeHtml($article['status']) ?>"><?= escapeHtml($article['status']) ?></span></div><?php endforeach; ?>
                    <?php if (!$recentArticles): ?><p class="subtle">No articles have been created yet.</p><?php endif; ?>
                </div></section>
                <section class="panel"><div class="panel-head"><h2>Latest feedback</h2><a class="button" href="?tab=feedback">Review</a></div><div class="panel-body">
                    <?php foreach ($recentFeedback as $item): ?><div class="list-item"><div><strong><?= escapeHtml($item['name']) ?></strong><span class="subtle"><?= escapeHtml(mb_strimwidth($item['message'], 0, 58, '...')) ?></span></div><span class="tag <?= escapeHtml($item['status']) ?>"><?= escapeHtml($item['status']) ?></span></div><?php endforeach; ?>
                    <?php if (!$recentFeedback): ?><p class="subtle">No visitor feedback yet.</p><?php endif; ?>
                </div></section>
            </div>
        <?php elseif ($tab === 'homepage'): ?>
            <section class="panel"><div class="panel-head"><h2>Homepage publishing controls</h2><a class="button light" href="?tab=media">Open media library</a></div><div class="panel-body">
                <form method="post"><input type="hidden" name="csrf" value="<?= csrfToken() ?>"><input type="hidden" name="action" value="save_homepage">
                    <label>Hero headline</label><input name="hero_title" required value="<?= escapeHtml($siteSettings['hero_title']) ?>">
                    <label>Hero description</label><textarea name="hero_description" required><?= escapeHtml($siteSettings['hero_description']) ?></textarea>
                    <label>Hero byline</label><input name="hero_byline" value="<?= escapeHtml($siteSettings['hero_byline']) ?>">
                    <div class="form-grid"><div><label>Homepage video</label><select name="homepage_video"><option value="">No video</option><?php foreach ($mediaFiles as $file): ?><?php if (in_array($file['extension'], ['mp4', 'webm'], true)): ?><option value="<?= escapeHtml($file['path']) ?>" <?= $siteSettings['homepage_video'] === $file['path'] ? 'selected' : '' ?>><?= escapeHtml($file['path']) ?></option><?php endif; ?><?php endforeach; ?></select></div><div><label>Video poster image</label><select name="homepage_poster"><option value="">No poster image</option><?php foreach ($mediaFiles as $file): ?><?php if (in_array($file['extension'], ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)): ?><option value="<?= escapeHtml($file['path']) ?>" <?= $siteSettings['homepage_poster'] === $file['path'] ? 'selected' : '' ?>><?= escapeHtml($file['path']) ?></option><?php endif; ?><?php endforeach; ?></select></div></div>
                    <label>Video caption</label><input name="homepage_video_caption" value="<?= escapeHtml($siteSettings['homepage_video_caption']) ?>">
                    <button class="button" type="submit">Publish homepage changes</button>
                </form>
            </div></section>
        <?php elseif ($tab === 'media'): ?>
            <section class="panel"><div class="panel-head"><h2>Upload media</h2></div><div class="panel-body">
                <form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?= csrfToken() ?>"><input type="hidden" name="action" value="upload_media"><label for="media-file">Image or video file</label><input id="media-file" name="media_file" type="file" accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm" required><p class="subtle">JPG, PNG, WebP, GIF, MP4, or WebM. Maximum 100 MB.</p><button class="button" type="submit">Upload media</button></form>
            </div></section>
            <section class="panel"><div class="panel-head"><h2>Media library</h2><a class="button" href="?tab=homepage">Use on homepage</a></div><div class="table-wrap"><table><thead><tr><th>File</th><th>Type</th><th>Size</th><th>Preview</th></tr></thead><tbody><?php foreach ($mediaFiles as $file): ?><tr><td><strong><?= escapeHtml($file['path']) ?></strong></td><td><?= escapeHtml(strtoupper($file['extension'])) ?></td><td><?= escapeHtml(number_format($file['size'] / 1024, 1)) ?> KB</td><td><?php if (in_array($file['extension'], ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)): ?><img src="../<?= escapeHtml($file['path']) ?>" alt="" style="width:72px;height:46px;object-fit:cover;border:1px solid #dce5ed;"><?php else: ?><video src="../<?= escapeHtml($file['path']) ?>" muted preload="metadata" style="width:72px;height:46px;background:#16233a;"></video><?php endif; ?></td></tr><?php endforeach; ?><?php if (!$mediaFiles): ?><tr><td colspan="4" class="subtle">No media files found.</td></tr><?php endif; ?></tbody></table></div></section>
        <?php elseif ($tab === 'articles'): ?>
            <section class="panel"><div class="panel-head"><h2><?= $articleToEdit ? 'Edit article' : 'Create article' ?></h2><?php if ($articleToEdit): ?><a class="button light" href="?tab=articles">Cancel edit</a><?php endif; ?></div><div class="panel-body">
                <form method="post"><input type="hidden" name="csrf" value="<?= csrfToken() ?>"><input type="hidden" name="action" value="save_article"><input type="hidden" name="article_id" value="<?= (int) ($articleToEdit['id'] ?? 0) ?>"><input type="hidden" name="return_tab" value="articles">
                    <div class="form-grid"><div><label>Title</label><input name="title" required value="<?= escapeHtml($articleToEdit['title'] ?? '') ?>"></div><div><label>Slug</label><input name="slug" value="<?= escapeHtml($articleToEdit['slug'] ?? '') ?>"></div><div><label>Category</label><select name="category_id"><option value="">No category</option><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>" <?= (string) ($articleToEdit['category_id'] ?? '') === (string) $category['id'] ? 'selected' : '' ?>><?= escapeHtml($category['name']) ?></option><?php endforeach; ?></select></div><div><label>Status</label><select name="status"><?php foreach (['draft', 'published', 'archived'] as $status): ?><option value="<?= $status ?>" <?= ($articleToEdit['status'] ?? 'draft') === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option><?php endforeach; ?></select></div></div>
                    <label>Short summary</label><input name="excerpt" value="<?= escapeHtml($articleToEdit['excerpt'] ?? '') ?>"><label>Image URL</label><input name="image_url" type="url" value="<?= escapeHtml($articleToEdit['image_url'] ?? '') ?>"><label>Content (HTML is supported)</label><textarea name="content" required><?= escapeHtml($articleToEdit['content'] ?? '') ?></textarea><label class="checkbox"><input name="is_featured" type="checkbox" <?= !empty($articleToEdit['is_featured']) ? 'checked' : '' ?>> Feature this article on the homepage</label><button class="button" type="submit"><?= $articleToEdit ? 'Save changes' : 'Create article' ?></button>
                </form>
            </div></section>
            <section class="panel"><div class="panel-head"><h2>All articles</h2></div><div class="table-wrap"><table><thead><tr><th>Article</th><th>Category</th><th>Status</th><th>Updated</th><th>Actions</th></tr></thead><tbody><?php foreach ($articles as $article): ?><tr><td><strong><?= escapeHtml($article['title']) ?></strong></td><td><?= escapeHtml($article['category_name'] ?? 'Uncategorised') ?></td><td><span class="tag <?= escapeHtml($article['status']) ?>"><?= escapeHtml($article['status']) ?></span></td><td><?= escapeHtml(date('M j, Y', strtotime($article['updated_at']))) ?></td><td><div class="actions"><a class="button light" href="?tab=articles&edit=<?= (int) $article['id'] ?>">Edit</a><form class="inline-form" method="post" onsubmit="return confirm('Delete this article?');"><input type="hidden" name="csrf" value="<?= csrfToken() ?>"><input type="hidden" name="action" value="delete_article"><input type="hidden" name="id" value="<?= (int) $article['id'] ?>"><button class="button danger">Delete</button></form></div></td></tr><?php endforeach; ?></tbody></table></div></section>
        <?php elseif ($tab === 'categories'): ?>
            <section class="panel"><div class="panel-head"><h2><?= $categoryToEdit ? 'Edit category' : 'Add category' ?></h2><?php if ($categoryToEdit): ?><a class="button light" href="?tab=categories">Cancel edit</a><?php endif; ?></div><div class="panel-body">
                <form method="post"><input type="hidden" name="csrf" value="<?= csrfToken() ?>"><input type="hidden" name="action" value="save_category"><input type="hidden" name="category_id" value="<?= (int) ($categoryToEdit['id'] ?? 0) ?>">
                    <div class="form-grid"><div><label>Name</label><input name="name" required value="<?= escapeHtml($categoryToEdit['name'] ?? '') ?>"></div><div><label>Slug</label><input name="slug" value="<?= escapeHtml($categoryToEdit['slug'] ?? '') ?>"></div><div><label>Icon</label><input name="icon" maxlength="10" value="<?= escapeHtml($categoryToEdit['icon'] ?? '') ?>"></div><div><label>Description</label><input name="description" maxlength="255" value="<?= escapeHtml($categoryToEdit['description'] ?? '') ?>"></div></div>
                    <button class="button" type="submit"><?= $categoryToEdit ? 'Save category' : 'Add category' ?></button>
                </form>
            </div></section>
            <section class="panel"><div class="panel-head"><h2>Publishing categories</h2></div><div class="table-wrap"><table><thead><tr><th>Category</th><th>Slug</th><th>Articles</th><th>Actions</th></tr></thead><tbody><?php foreach ($categoryItems as $category): ?><tr><td><strong><?= escapeHtml($category['icon'] ?: '') ?> <?= escapeHtml($category['name']) ?></strong><br><span class="subtle"><?= escapeHtml($category['description'] ?? '') ?></span></td><td><?= escapeHtml($category['slug']) ?></td><td><?= (int) $category['article_count'] ?></td><td><div class="actions"><a class="button light" href="?tab=categories&edit=<?= (int) $category['id'] ?>">Edit</a><form class="inline-form" method="post" onsubmit="return confirm('Delete this category? Articles in it will become uncategorised.');"><input type="hidden" name="csrf" value="<?= csrfToken() ?>"><input type="hidden" name="action" value="delete_category"><input type="hidden" name="id" value="<?= (int) $category['id'] ?>"><button class="button danger">Delete</button></form></div></td></tr><?php endforeach; ?></tbody></table></div></section>
        <?php elseif ($tab === 'feedback'): ?>
            <section class="panel"><div class="panel-head"><h2>Visitor feedback</h2></div><div class="table-wrap"><table><thead><tr><th>Visitor</th><th>Message</th><th>Received</th><th>Status</th><th>Actions</th></tr></thead><tbody><?php foreach ($feedbackItems as $item): ?><tr><td><strong><?= escapeHtml($item['name']) ?></strong><br><span class="subtle"><?= escapeHtml($item['email']) ?></span></td><td class="message"><?= nl2br(escapeHtml($item['message'])) ?></td><td><?= escapeHtml(date('M j, Y', strtotime($item['created_at']))) ?></td><td><span class="tag <?= escapeHtml($item['status']) ?>"><?= escapeHtml($item['status']) ?></span></td><td><div class="actions"><?php foreach (['approved' => 'Approve', 'rejected' => 'Reject'] as $status => $label): ?><form class="inline-form" method="post"><input type="hidden" name="csrf" value="<?= csrfToken() ?>"><input type="hidden" name="action" value="feedback_status"><input type="hidden" name="id" value="<?= (int) $item['id'] ?>"><input type="hidden" name="status" value="<?= $status ?>"><button class="button <?= $status === 'rejected' ? 'danger' : '' ?>"><?= $label ?></button></form><?php endforeach; ?><form class="inline-form" method="post" onsubmit="return confirm('Delete this feedback?');"><input type="hidden" name="csrf" value="<?= csrfToken() ?>"><input type="hidden" name="action" value="delete_feedback"><input type="hidden" name="id" value="<?= (int) $item['id'] ?>"><button class="button light">Delete</button></form></div></td></tr><?php endforeach; ?></tbody></table></div></section>
        <?php elseif ($tab === 'subscribers'): ?>
            <section class="panel"><div class="panel-head"><h2>Newsletter subscribers</h2></div><div class="table-wrap"><table><thead><tr><th>Email</th><th>Subscribed</th><th>State</th><th>Action</th></tr></thead><tbody><?php foreach ($subscribers as $subscriber): ?><tr><td><strong><?= escapeHtml($subscriber['email']) ?></strong></td><td><?= escapeHtml(date('M j, Y', strtotime($subscriber['subscribed_at']))) ?></td><td><span class="tag <?= $subscriber['is_active'] ? 'approved' : 'rejected' ?>"><?= $subscriber['is_active'] ? 'active' : 'inactive' ?></span></td><td><form class="inline-form" method="post"><input type="hidden" name="csrf" value="<?= csrfToken() ?>"><input type="hidden" name="action" value="subscriber_status"><input type="hidden" name="id" value="<?= (int) $subscriber['id'] ?>"><input type="hidden" name="is_active" value="<?= $subscriber['is_active'] ? 0 : 1 ?>"><button class="button <?= $subscriber['is_active'] ? 'danger' : '' ?>"><?= $subscriber['is_active'] ? 'Deactivate' : 'Reactivate' ?></button></form></td></tr><?php endforeach; ?></tbody></table></div></section>
        <?php elseif ($tab === 'jobs'): ?>
            <section class="panel"><div class="panel-head"><h2>Job listings</h2></div><div class="table-wrap"><table><thead><tr><th>Role</th><th>Company</th><th>Location</th><th>Status</th><th>Actions</th></tr></thead><tbody><?php foreach ($jobs as $job): ?><tr><td><strong><?= escapeHtml($job['title']) ?></strong></td><td><?= escapeHtml($job['company_name']) ?></td><td><?= escapeHtml($job['location']) ?></td><td><span class="tag <?= escapeHtml($job['status']) ?>"><?= escapeHtml($job['status']) ?></span></td><td><div class="actions"><form class="inline-form" method="post"><input type="hidden" name="csrf" value="<?= csrfToken() ?>"><input type="hidden" name="action" value="job_status"><input type="hidden" name="id" value="<?= (int) $job['id'] ?>"><input type="hidden" name="status" value="<?= $job['status'] === 'open' ? 'closed' : 'open' ?>"><button class="button <?= $job['status'] === 'open' ? 'danger' : '' ?>"><?= $job['status'] === 'open' ? 'Close' : 'Reopen' ?></button></form><form class="inline-form" method="post" onsubmit="return confirm('Delete this job?');"><input type="hidden" name="csrf" value="<?= csrfToken() ?>"><input type="hidden" name="action" value="delete_job"><input type="hidden" name="id" value="<?= (int) $job['id'] ?>"><button class="button light">Delete</button></form></div></td></tr><?php endforeach; ?></tbody></table></div></section>
        <?php endif; ?>
    </main>
</div>
</body>
</html>