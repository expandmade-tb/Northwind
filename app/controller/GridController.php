<?php

namespace controller;

use dbgrid\DbGrid;

class GridController extends BaseController {
    protected string $defaultview = 'Crud';
    protected DbGrid $grid;

    function __construct() {
        parent::__construct();
        $this->html_compress = false;
    }

    public function index () : void {
       $this->grid(1);
    }

    public function grid(int $page) : void {
        $this->data['dbgrid'] = $this->grid->grid($page);
        $this->view($this->defaultview);
    }

    public function clear() : void {
        $this->grid->clear();
        $this->data['dbgrid'] = $this->grid->grid();
        $this->view($this->defaultview);
    }
}