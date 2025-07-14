<?php

/**
 * Users Maintenance Controller
 * Version 1.4.0
 * Author: expandmade / TB
 * Author URI: https://expandmade.com
 */

namespace controller;

use dbgrid\DbCrud;
use helper\CryptStrSingleton;
use helper\Helper;
use helper\Session;
use models\user_clients_model;
use models\users_model;

class Users extends BaseController {
    private DbCrud $crud;

    function __construct() {
        parent::__construct();
        
        $this->crud = new DbCrud(new users_model());
        $this->crud->grid_delete = '';
        $this->crud->grid_show = '';
        $this->crud->addFields('Name,Mail,AccessControl,RoleId,ValidUntil');
        $this->crud->editFields('UserId,ClientId,Name,Mail,AccessControl,RoleId,ValidUntil');
        $this->crud->gridFields('UserId,Name,Mail,ValidUntil');
        $this->crud->readonlyFields('UserId,ClientId');
        $this->crud->fieldType('ValidUntil', 'date');
        $this->crud->setRelation('RoleId', 'Name', 'Roles');
        $this->crud->searchFields('Name,Mail');

        $this->crud->fieldTitles(
            'Name,Mail,AccessControl,ValidUntil,ClientId,UserId',
            'Name,Mail,Access Control, Valid until,Client ID, User ID'
        );

        $this->crud->callbackInsert([$this, 'callback_insert']);
        $this->crud->callbackUpdate([$this, 'callback_update']);
        $this->crud->formatField('Mail', [$this, 'callback_formatMail']);
    }

    public function callback_update(mixed $id, array $data) : bool {
        $crypt = CryptStrSingleton::getInstance(Helper::env('app_secret'));
        $data['Mail'] = $crypt->encrypt($data['Mail']); // encrypt Mail
        return $this->crud->model()->update($id, $data);
    }

    public function callback_formatMail (string $source, string $value) : string {
        if ( empty($value) )
            return $value;
            
        $crypt = CryptStrSingleton::getInstance(Helper::env('app_secret'));
        $result = $crypt->decrypt($value); // decrypt Mail

        if ( $result === false )
            return $value;
        else
            return $result;
    }

    public function callback_insert(array $data) : void {
        $crypt = CryptStrSingleton::getInstance(Helper::env('app_secret'));
        $userid = uniqid();
        $clientid = bin2hex(random_bytes(16));
        $data['UserId'] = $userid;
        $data['ClientId'] = $clientid;
        $data['KeyCode'] = 'register-'.$userid;
        $data['Mail'] = $crypt->encrypt($data['Mail']);
        $this->crud->model()->insert($data);
        Auth::create_registration($data);
    }

    public function index () : void {
       $this->grid(1);
    }

    public function add() : void {
        $this->data['dbgrid'] = $this->crud->form('add');
        $this->view('Crud');
    }

    public function edit(string $id) : void {
        $this->data['dbgrid'] = $this->crud->form('edit', $id);
        $this->view('Crud');
    }

    public function delete(string $id) : void {
        if ( $id == Session::instance()->get('user_id', 'unkwonw') || $this->crud->rowcount() == 1 ) 
            $this->data['dbgrid'] = $this->crud->form('edit', $id, 'you can NOT delete this user');
        else {
            $user_clients = new user_clients_model();
            $user_clients->delete_all($id);
            $this->crud->delete($id);
            $this->data['dbgrid'] = $this->crud->grid();
        }

        $this->view('Crud');
    }

    public function grid(int $page) : void {
        $this->data['dbgrid'] = $this->crud->grid($page);
        $this->view('Crud');
    }

    public function clear() : void {
        $this->crud->clear();
        $this->data['dbgrid'] = $this->crud->grid();
        $this->view('Crud');
    }
}