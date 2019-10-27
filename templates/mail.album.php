<div style="padding-bottom: 20px; padding-right: 20px; display: inline-block;">
    <a href="<?php echo $album['link'] ?>">
        <img src="<?php echo $album['cover_medium'] ?>" alt="Album cover" crossorigin="anonymous" width="264" height="264" style="border-radius: 5px">
    </a>
    <div>
        <div>
            <a style="color: white" title="<?php echo $album['title'] ?>" href="<?php echo $album['link'] ?>">
                <?php echo $album['title'] ?>
            </a>
        </div>
        <div style="color: #92929d">
            <small>by </small>
            <a style="color: #92929d" title="<?php echo $album['artist']['name'] ?>"
               href="<?php echo $album['artist']['link'] ?>">
                <?php echo $album['artist']['name'] ?>
            </a>
        </div>
        <small style="color: #92929d">Released on <?php echo $album['release_date'] ?></small>
    </div>
</div>
