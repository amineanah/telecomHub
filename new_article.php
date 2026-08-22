<?php
session_start();
// Simple admin page to create articles without editing code.
// Change this password before using on a public server.
const ADMIN_PASS = 'admin123';

// If not logged in, handle login form
if (!empty($_POST['action']) && $_POST['action'] === 'login') {
    $pw = $_POST['password'] ?? '';
    if ($pw === ADMIN_PASS) {
        $_SESSION['is_admin'] = true;
    } else {
        $error = 'Invalid password';
    }
}

if (empty($_SESSION['is_admin'])) {
    // show login form
    ?>
    <!doctype html>
    <html><head><meta charset="utf-8"><title>Admin Login</title></head><body>
    <h2>Admin Login</h2>
    <?php if (!empty($error)) echo '<p style="color:red">'.htmlspecialchars($error).'</p>'; ?>
    <form method="post">
      <input type="hidden" name="action" value="login">
      <label>Password: <input type="password" name="password"></label>
      <button type="submit">Login</button>
    </form>
    </body></html>
    <?php
    exit;
}

// Logged in: show form and process submissions
require_once __DIR__ . '/../db.php';

// handle form POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $excerpt = trim($_POST['excerpt'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $category_id = $_POST['category_id'] ?: null;
    $status = ($_POST['status'] ?? 'draft');
    $is_featured = !empty($_POST['is_featured']) ? 1 : 0;

    if ($title === '') {
        $err = 'Title is required.';
    } else {
        if ($slug === '') {
            // create simple slug
            $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim($title)));
            $slug = trim($slug, '-');
        }

        // handle upload
        $image_url = null;
        if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploads = __DIR__ . '/../uploads';
            if (!is_dir($uploads)) mkdir($uploads, 0755, true);
            $name = bin2hex(random_bytes(8)) . '-' . basename($_FILES['image']['name']);
            $target = $uploads . DIRECTORY_SEPARATOR . $name;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                $image_url = 'uploads/' . $name;
            }
        }

        // insert
        $sql = "INSERT INTO articles (title, slug, excerpt, content, image_url, category_id, is_featured, status, published_at) VALUES (:title, :slug, :excerpt, :content, :image_url, :category_id, :is_featured, :status, :published_at)";
        $published_at = $status === 'published' ? date('Y-m-d H:i:s') : null;
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':title' => $title,
            ':slug' => $slug,
            ':excerpt' => $excerpt,
            ':content' => $content,
            ':image_url' => $image_url,
            ':category_id' => $category_id,
            ':is_featured' => $is_featured,
            ':status' => $status,
            ':published_at' => $published_at
        ]);

        $newId = $pdo->lastInsertId();
        header('Location: ../site-visit-sbc-training.html');
        exit;
    }
}

$cats = $pdo->query('SELECT id, name FROM categories ORDER BY id')->fetchAll();

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>New Article — Admin</title>
  <style>body{font-family:Arial,Helvetica,sans-serif;padding:18px}label{display:block;margin:8px 0}input[type=text],textarea,select{width:100%;max-width:740px;padding:8px}button{padding:8px 12px}</style>
</head>
<body>
  <h1>Create Article</h1>
  <?php if (!empty($err)) echo '<p style="color:red">'.htmlspecialchars($err).'</p>'; ?>
  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="action" value="create">
    <label>Title<br><input name="title" type="text" required></label>
    <label>Slug (optional)<br><input name="slug" type="text"></label>
    <label>Excerpt<br><input name="excerpt" type="text"></label>
    <label>Content (HTML allowed)<br><textarea name="content" rows="10"></textarea></label>
    <label>Image (optional)<br><input name="image" type="file" accept="image/*"></label>
    <label>Category<br><select name="category_id">
      <option value="">-- none --</option>
      <?php foreach ($cats as $c): ?>
        <option value="<?=htmlspecialchars($c['id'])?>"><?=htmlspecialchars($c['name'])?></option>
      <?php endforeach; ?>
    </select></label>
    <label>Status<br>
      <select name="status"><option value="draft">Draft</option><option value="published">Published</option></select>
    </label>
    <label><input type="checkbox" name="is_featured"> Feature on homepage</label>
    <div style="margin-top:12px">
      <button type="submit">Create Article</button>
    </div>
  </form>
  <p><a href="../Html%20code.html">Back to site</a></p>
</body>
</html>
