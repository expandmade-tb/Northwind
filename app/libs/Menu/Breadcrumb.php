<?php

namespace Menu;

class Breadcrumb {
   private array $menu;
   private array $path = [];
   private array $context = [];

   public function __construct(string $menuFile = 'menu.php', string $contextFile = 'context_continuation.php') {
      $this->menu = require(__DIR__ . '/' . $menuFile);
      $this->context = file_exists(__DIR__ . '/' . $contextFile)
         ? require(__DIR__ . '/' . $contextFile)
         : [];
   }

   /**
    * Generate the breadcrumb HTML based on current path
    */
   public function render(string $currentPath): string {
      $this->path = [];

      // Normalize path → extract controller (first segment)
      $segments = explode('/', parse_url($currentPath, PHP_URL_PATH));
      $base = '/' . ($segments[1] ?? '');

      // Try to find the path in the menu
      if ($this->findPath($this->menu, $base)) {
         return $this->buildHtmlBootstrap($this->path);
      }

      // Check for mapped continuation
      if (isset($this->context[$base])) {
         $map = $this->context[$base];
         $this->findPath($this->menu, $map['base']);
         return $this->buildHtmlBootstrap($this->path, $map['trail'] ?? []);
      }

      return '';
   }

   /**
    * Recursively locate a path from the menu
    */
   private function findPath(array $menu, string $currentPath): bool {
      foreach ($menu as $item => $link) {
         if (is_array($link)) {
            if ($this->findPath($link, $currentPath)) {
               $this->path[] = [$item, '#'];
               return true;
            }
         } elseif ($link === $currentPath) {
            $this->path[] = [$item, '#'];
            return true;
         }
      }
      return false;
   }

   /**
    * Build the Bootstrap breadcrumb HTML with optional extra steps
    */
   private function buildHtmlBootstrap(array $path, array $extra = []): string {
      $path = array_reverse($path);
      $html = '<nav aria-label="breadcrumb" class="bg-dark py-2 px-3">';
      $html .= '<ol class="breadcrumb mb-0 text-white">';

      // Static Home
      $html .= '<li class="breadcrumb-item"><i class="bi bi-compass"></i>&nbsp;Home</li>';

      foreach ($path as [$label,]) {
         [$labelText, $icon] = explode('|', $label . '|');
         $iconHtml = $icon
            ? '<img src="' . IMAGES . '/' . $icon . '" alt="" width="16" height="16" class="me-1">'
            : '';
         $html .= "<li class=\"breadcrumb-item\">$iconHtml$labelText</li>";
      }

      foreach ($extra as $i => [$labelText, $icon]) {
         $iconHtml = $icon
            ? '<img src="' . IMAGES . '/' . $icon . '" alt="" width="16" height="16" class="me-1">'
            : '';
         $active = ($i === array_key_last($extra)) ? ' active" aria-current="page' : '';
         $html .= "<li class=\"breadcrumb-item$active\">$iconHtml$labelText</li>";
      }

      $html .= '</ol></nav>';
      return $html;
   }
}
