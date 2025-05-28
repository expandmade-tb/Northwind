<?php

/**
 * Upgrade Controller
 * Version 1.0.2
 * Author: expandmade / TB
 * Author URI: https://expandmade.com
 */

namespace controller;

use database\DBTable;
use Formbuilder\Formbuilder;
use helper\Helper;
use models\upgrades_model;

class Upgrade extends BaseController {
    function __construct() {
        parent::__construct();
    }

    public function check_upgrade_code ( string $value ) : string  {
        if ( $value != Helper::env('upgrade_code', 'invalid value') )
            return 'invalid code';

        return '';
    }

    public function index () : void {
        $sql = '';
        $upgrade_sql = DBTable::getSQL('upgrade.sql');
        $form = new Formbuilder('upgrade');
        $form->html('<h2>Upgrade Database</h2>');

        if ( $upgrade_sql === false ) {
            $form->html('<p>no upgrade available</p>');
        }
        else {
            $sql = $upgrade_sql;
            $form->text('upgrade_code')->rule([$this,'check_upgrade_code']);
            $form->submit('upgrade');
        }

        if ( $form->submitted() ) {
            $field_list='upgrade_code';
            $form_data = $form->validate($field_list);

            if ( $form_data === false ) 
                $form->message(('invalid form validation'));
            else
                if ($form->ok() ) { 
                    $p = strpos($sql, PHP_EOL);
                    $version_info = $p === false ? 'unknown' : substr($sql, 0, $p);
                    $u = new upgrades_model();
                    $u->database()->beginTransaction();
                    $result = $u->database()->exec($sql);

                    if ( $result === false ) {
                        $u->database()->rollBack();
                        $form->reset()->message('error');
                    }
                    else {
                        $result = $u->insert(['run_date'=>time(), 'version_info'=>$version_info ]);
                        $u->database()->commit();
                        rename(APP.'/sql/upgrade.sql', APP."/sql/upgrade-$version_info");
                        $form->reset()->message('done', 'class="alert alert-success" ');
                    }
                }
        }
        
        $this->data['form'] = $form->render();
        $this->view('Upgrade');
    }
}