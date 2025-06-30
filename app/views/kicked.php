<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title><?= $title ?></title>
    <?php foreach($css_files as $file): ?>
      <link href="<?= $file; ?>" rel="stylesheet" />
    <?php endforeach; ?>  
  </head>
  <body`>
    <div id="container" style="white-space:nowrap">
        <div style="margin-top: 50px; margin-left: 30px">
          <h3><i class="bi bi-cone-striped" style="color: red"></i>&nbsp;Sorry, you have been kicked</h3>
          <p><?php echo $reason??'' ?></p>
        </div>
    </div>
  </body>
</html>