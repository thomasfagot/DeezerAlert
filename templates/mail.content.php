<?php /** @var \DeezerAlert\Playlist $playlist */ ?>
<!doctype html>
<html lang="en">
<head>
    <title>New release on Deezer</title>
</head>
<body style="margin: 0; background-color: #121216; color: white; font-family: Deezer, Arial, sans-serif; padding: 20px;">
    <h2>New releases</h2>
    <div>
        <div style="padding-bottom: 20px; padding-right: 20px; display: inline-block;">
            <a href="<?php echo $playlist->getLink() ?>" title="Open in browser">
                <img src="<?php echo $playlist->getCover() ?>" alt="Playlist cover" crossorigin="anonymous" width="264" height="264" style="border-radius: 5px">
            </a>
            <div>
                <div>
                    <a style="color: white" title="Open in Deezer app" href="<?php echo $playlist->getRedirectToAppLink() ?>">
                        <?php echo $playlist->getName() ?>
                    </a>
                </div>
                <div style="color: #92929d">
                    <small><?php echo count($playlist->getTrackCollection()); ?> songs</small>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
