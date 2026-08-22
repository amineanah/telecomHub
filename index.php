<?php
require_once __DIR__ . '/db.php';

// Fetch latest published articles (limit 4)
$stmt = $pdo->prepare("SELECT id, title, slug, excerpt, image_url, published_at, is_featured FROM articles WHERE status = 'published' ORDER BY published_at DESC LIMIT 4");
$stmt->execute();
$articles = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TelecomHub | Home</title>
    <style>
        /* minimal copy of existing styles required for the homepage */
        body{font-family:Arial,Helvetica,sans-serif;background:#f6f8fb;color:#101828}
        .container{width:92%;max-width:1450px;margin:30px auto}
        .article-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:18px}
        .article{border:1px solid #e5e7eb;border-radius:9px;overflow:hidden;background:white}
        .article img{width:100%;height:180px;object-fit:cover}
        .article-content{padding:16px}
        .article-category{color:#1264e8;font-size:11px;font-weight:bold}
        .article h3{font-size:17px;margin:8px 0;line-height:1.35}
        .article p{color:#667085;font-size:13px}
        .article-date{margin-top:12px;color:#8a93a3;font-size:11px}
        .add-article-btn{position:fixed;right:22px;bottom:22px;background:#1264e8;color:#fff;width:56px;height:56px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:28px;box-shadow:0 8px 20px rgba(0,0,0,.12);z-index:9999;text-decoration:none}
    </style>
</head>
<body>

<main class="container">

    <h1>Latest Articles</h1>

    <section class="articles">
        <div class="article-grid">
            <?php if (empty($articles)): ?>
                <p>No published articles yet.</p>
            <?php else: ?>
                <?php foreach ($articles as $a): ?>
                <article class="article">
                    <a href="article.php?id=<?=htmlspecialchars($a['id'])?>">
                        <img src="<?=htmlspecialchars($a['image_url'] ?: 'https://images.unsplash.com/photo-1544724569-5f546fd6f2b5?auto=format&fit=crop&w=800&q=80')?>" alt="<?=htmlspecialchars($a['title'])?>">
                    </a>
                    <div class="article-content">
                        <div class="article-category"><?= $a['is_featured'] ? 'FEATURED' : 'ARTICLE' ?></div>
                        <h3><a href="article.php?id=<?=htmlspecialchars($a['id'])?>"><?=htmlspecialchars($a['title'])?></a></h3>
                        <p><?=htmlspecialchars($a['excerpt'])?></p>
                        <div class="article-date"><?=htmlspecialchars(date('F j, Y', strtotime($a['published_at'])))?></div>
                    </div>
                </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

</main>

<a class="add-article-btn" href="admin/new_article.php" title="Add article">+</a>

</body>
</html>
