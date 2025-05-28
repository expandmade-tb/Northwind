<?php

namespace controller;

use dbgrid\DbCrud;

class CrudController extends BaseController {
    protected string $defaultview = 'Crud';
    protected DbCrud $crud;

    function __construct() {
        parent::__construct();
    }

    public function index () : void {
       $this->grid(1);
    }

    public function add() : void {
        $this->data['dbgrid'] = $this->crud->form('add');
        $this->view($this->defaultview);
    }

    public function edit(string $id) : void {
        $this->data['dbgrid'] = $this->crud->form(action: 'edit', id: $id);
        $this->view($this->defaultview);
    }

    public function delete(string $id) : void {
        $result = $this->crud->delete($id);

        if ( $result === false )
            $this->data['dbgrid'] = $this->crud->grid();
        else
            $this->data['dbgrid'] = $this->crud->grid($result);
        
            $this->view($this->defaultview);
        }

    public function grid(int $page) : void {
        $this->data['dbgrid'] = $this->crud->grid($page);
        $this->view($this->defaultview);
    }

    public function clear() : void {
        $this->crud->clear();
        $this->data['dbgrid'] = $this->crud->grid();
        $this->view($this->defaultview);
    }

    public function show($id) : void {
        $this->data['dbgrid'] = $this->crud->form('show', $id);
        $this->view($this->defaultview);
    }
}