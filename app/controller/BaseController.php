<?php

/**
 * Base Controller
 * Version 1.0.4
 * Author: expandmade / TB
 * Author URI: https://expandmade.com
 */

namespace controller;

use Minifier\TinyMinify;
use helper\Helper;
use Menu\Breadcrumb;
use Menu\MenuBar;
use Router\Router;

class BaseController {
    protected bool $html_compress = true;
    protected array $data = [];

    function __construct() {
        $this->data['css_files'] = [
            'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css',
            'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css',
            STYLESHEET.'/styles.min.css'
        ];

        $breadcrumb = new Breadcrumb();

        $this->data['js_files'] = ["https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"];
        $this->data['menu'] = MenuBar::factory()->get('');
        $this->data['breadcrumb'] = $breadcrumb->render(Router::instance()->current_uri());
        $this->data['icon'] = IMAGES.Helper::env('app_image');
        $this->data['title'] = Helper::env('app_title', 'Remote Tables');
        $this->data['notification'] = '';
    }

    /**
     * @return void
     */
    protected function index() {} 

    /**
     * @return void
     */
    protected function view (string $view, array $data=[]) { 
        if ( !empty($data) )
            extract($data);
        else
            extract($this->data);

        $file = APP.'/views/'.str_replace('\\', DIRECTORY_SEPARATOR, $view).'.php';

        if ( $this->html_compress == true ) 
            ob_start([$this, 'compress']);

        require_once $file;

        if ( $this->html_compress == true ) 
            ob_end_flush();
    }

    public function compress(string $buffer) : string {
        return TinyMinify::html($buffer);
    }
}