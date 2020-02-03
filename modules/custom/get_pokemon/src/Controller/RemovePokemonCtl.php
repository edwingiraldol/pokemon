<?php


namespace Drupal\get_pokemon\Controller;
/*
    Class: RemovePokemonCtl

    Controlle to remove pokemon from DB
*/
class RemovePokemonCtl {

    /**
     * Form constructor.
     *
     * @param array $id
     * remove pokemon from DB by ID
     */
    public function remove($id){
        \Drupal::database()->delete('pokemon')
            ->condition('id', $id)
            ->execute();
        header('Location: /get-pokemon');
        die();

    }

}
