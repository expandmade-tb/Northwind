<?php

/**
 * Menu builder
 * Version 1.3.0
 * Author: expandmade / TB
 * Author URI: https://expandmade.com
 */

namespace Menu;

use helper\Helper;
use helper\Session;

class MenuBar { 
   private static ?MenuBar $instance = null;
   private mixed $disallowedAccess;

   public static function factory () : MenuBar {
      if (self::$instance == null)
         self::$instance = new MenuBar();

      return self::$instance;
   }

   /**
    * BULMA: build a menu
    */
   private function build_menu_bulma (array $menu, int $level=0) : string {  /* recursive function ! */
      $result = '';

      foreach ($menu as $item => $link) {
         if ( is_array($link) ) {
            list($text, $icon) = explode('|', $item.'|');

            if ( !empty($icon) )
               $result .= '<div class="navbar-item has-dropdown is-hoverable"><a class="navbar-link"><img src="'.IMAGES.'/'.$icon.'" width="24" height="24">&nbsp;'.$text.'</a><div class="navbar-dropdown">';
            else
               $result .= '<div class="navbar-item has-dropdown is-hoverable"><a class="navbar-link">'.$text.'</a><div class="navbar-dropdown">';

            $i = $level + 1;
            $result .= $this->build_menu_bulma($link, $i).'</div></div>';
         }
         else {
            if ( $this->disallowedAccess === false )
               $disabled = '';
            else {
               $controller = str_replace('/', '', $link);
               $disabled = (in_array($controller, $this->disallowedAccess) === true) ? ' is-disabled' : ''; 
            }

            list($text, $icon) = explode('|', $item.'|');

            if ( !empty($icon) )
               $result .= '<a class="navbar-item'.$disabled.'" href="'.$link.'"><img src="'.IMAGES.'/'.$icon.'" width="24" height="24">&nbsp;'.$text.'</a>';
            else
               $result .= '<a class="navbar-item'.$disabled.'" href="'.$link.'">'.$text.'</a>';
         }
      }

      return $result;
   }

   /**
    * BOOTSTRAP: build a default menu
    */
    private function build_menu (array $menu, int $level=0) : string {  /* recursive function ! */
      $result = '';

      foreach ($menu as $item => $link) {
         if ( is_array($link) ) {
            list($text, $icon) = explode('|', $item.'|');

            if ( $level > 0 )
               if ( !empty($icon) )
                  $result .= '<li class="nav-item dropend"> <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"><img src="'.IMAGES.'/'.$icon.'" width="24" height="24">&nbsp;'.$text.'</a> <ul class="dropdown-menu dropdown-menu-dark">';
               else
                  $result .= '<li class="nav-item dropend"> <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">'.$text.'</a> <ul class="dropdown-menu dropdown-menu-dark">';
            else
               if ( !empty($icon) )
                  $result .= '<li class="nav-item dropdown"> <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"><img src="'.IMAGES.'/'.$icon.'" width="24" height="24">&nbsp;'.$text.'</a> <ul class="dropdown-menu dropdown-menu-dark">';
               else
                  $result .= '<li class="nav-item dropdown"> <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">'.$text.'</a> <ul class="dropdown-menu dropdown-menu-dark">';

            $i = $level + 1;
            $result .= $this->build_menu($link, $i).'</ul></li>';
         }
         else {
            if ( $this->disallowedAccess === false )
               $disabled = '';
            else {
               $controller = str_replace('/', '', $link);
               $disabled = (in_array($controller, $this->disallowedAccess) === true) ? 'disabled' : ''; 
            }

            list($text, $icon) = explode('|', $item.'|');

            if ( $level > 0 )
               if ( !empty($icon) )
                  $result .= '<li><a class="dropdown-item '.$disabled.' " href="'.$link.'"><img src="'.IMAGES.'/'.$icon.'" width="24" height="24">&nbsp;'.$text.'</a></li>';
               else
                  $result .= '<li><a class="dropdown-item '.$disabled.' " href="'.$link.'">'.$text.'</a></li>';
            else
               if ( !empty($icon) )
                  $result .= '<li class="nav-item '.$disabled.' "><a class="nav-link" href="'.$link.'"><img src="'.IMAGES.'/'.$icon.'" width="24" height="24">&nbsp;'.$text.'</a></li>';
               else
                  $result .= '<li class="nav-item '.$disabled.' "><a class="nav-link" href="'.$link.'">'.$text.'</a></li>';
         }
      }

      return $result;
   }

   public function get(string $css='bootstrap', string $menu='menu') : string {
      $menu_html = Session::instance()->get('menu_html');

      if ( empty($menu_html) ) {
         $this->disallowedAccess = Helper::disallowedAccess();
         $menu = require(__DIR__ . "/$menu.php");

         if ( $css == 'bulma')
            $menu_html = $this->build_menu_bulma($menu);
         else
            $menu_html = $this->build_menu($menu);
         
         Session::instance()->set('menu_html', $menu_html);
      }

      return $menu_html;
   }
}