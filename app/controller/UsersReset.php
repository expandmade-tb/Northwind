<?php

namespace controller;

use helper\CryptStrSingleton;
use helper\Helper;
use models\user_clients_model;
use models\users_model;

/**
 * 
 * This controller resets the users keycodes and client id's
 * 
 * USAGE !!! : 
 * 
 * 1. Enter the controller in the Routes exception list
 * 2. After successfull run, REMOVE it from the Routes exception list
 * 
 */

class UsersReset {
   public function index () : void {
      $msg = 'UsersReset started from IP '.Helper::get_ip_addr();
      Helper::log($msg);
      echo $msg;

      $users_model  = new users_model();
      $user_clients_model  = new user_clients_model();
      $location = Helper::env('tmp_location');
      $crypt = CryptStrSingleton::getInstance(Helper::env('app_secret'));

      $user_ids = $users_model->findColumn('UserId');

      foreach ($user_ids as $id) {
         $data = $users_model->find($id);

         if ($data !== false ) {
            // create new keycode, client id, de- and encrypt mail
            $keycode = bin2hex(random_bytes(32));
            $clientid = bin2hex(random_bytes(16));
            $mail = $crypt->decrypt($data['Mail']);
            $data['KeyCode'] = password_hash( $keycode,  PASSWORD_DEFAULT);
            $data['ClientId'] = $clientid;

            if ( $mail !== false )
               $data['Mail'] = $crypt->encrypt($mail);

            try {
               $users_model->database()->beginTransaction();

               if ( $users_model->update($id, $data) === true) {
                  // update tables in db
                  $user_clients_model->delete_all($id);
                  $users_model->database()->commit();
            
                  // create and save the new keyfile
                  $aname = strtolower(str_replace(' ','_', Helper::env('app_title')));
                  $uname = strtolower(str_replace(' ','_', $data['Name']));

                  $filename = "$location/$aname-$uname-$id.key";
                  $result = json_encode(['user_id'=>$id,'key_code'=>$keycode]);
                  $crypt = CryptStrSingleton::getInstance(Helper::env('app_secret'));
                  $cdata = $crypt->encrypt($result === false ? '' : $result);
                  
                  if ( file_put_contents($filename, Auth::KEY_HEADER.$cdata) !== false) 
                     Auth::create_registration($data);
               }
               else
                  $users_model->database()->rollBack();

            } catch (\Throwable $th) {
               $users_model->database()->rollBack();
            }
         }
      }

      $msg = 'UsersReset ended';
      Helper::log($msg);
      echo "<br>$msg";
   }
}