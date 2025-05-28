<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title><?php echo $title ?></title>
    <?php foreach($css_files as $file): ?>
      <link href="<?php echo $file; ?>" rel="stylesheet" />
    <?php endforeach; ?>  
  </head>
  <body id="page">
     <!-- Navigation bar -->
     <nav class="navbar is-dark" role="navigation" aria-label="main navigation">
        <div class="navbar-brand">
          <a class="navbar-item" href="/"><img src="<?php echo $icon ?>" alt="icon" width="32" height="32"><?php echo $title ?></a>
          <a role="button" class="navbar-icon navbar-burger burger" id="burger" onclick="toggleNavbar(this)" aria-label="menu" aria-expanded="false" data-target="navbarMenuHeroC">
            <span aria-hidden="true"></span>
            <span aria-hidden="true"></span>
            <span aria-hidden="true"></span>
            <span aria-hidden="true"></span>
          </a>
        </div>
        <div id="navbarMenuHeroC" class="navbar-menu">
          <?php echo $menu ?> 
        </div>
      </nav>    
    <br>