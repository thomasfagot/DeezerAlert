<!doctype html>
<html lang="en">
<head>
    <title>New release on Deezer</title>
</head>
<body style="margin: 0; background-color: #121216; color: white; font-family: Deezer, Arial, sans-serif; padding: 20px;">
    <h2>New releases</h2>
    <div>
        <?php foreach ($newContent as $album): ?>
            <?php include(__DIR__.'/mail.album.php') ?>
        <?php endforeach ?>
    </div>
</body>
</html>
