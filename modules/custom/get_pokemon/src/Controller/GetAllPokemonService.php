<?php

namespace Drupal\get_pokemon\Controller;
/*
    Class: GetAllPokemonService

    services to get pokemons
*/
class GetAllPokemonService {

    private $client;
    private $config;
    private $end_point;
    function __construct()
    {

        $this->config = \Drupal::config('get_pokemon.settings');

        $this->end_point = $this->config->get('end_point') == NULL ? 'https://pokeapi.co/api/v2' : $this->config->get('end_point');


        $this->client = \Drupal::httpClient();
    }
    /**
     * get_list_pokemon.
     *
     * @param string $name
     *
     * @return array
     *
     * pokemons with basic date.
     */
    public function get_list_pokemon($name){

        $response = array();
        try {
            if($name){
                $request = $this->client->get($this->end_point.'/pokemon/'.$name);

                $response[0] = json_decode($request->getBody(),TRUE);
            }else{

                for ($i = 1; $i <= 10; $i ++){

                    $request = $this->client->get($this->end_point.'/pokemon/'.$i);

                    $response[$i] = json_decode($request->getBody(),TRUE);
                }
            }
        }catch (\Exception $ex) {
            $this->drupal->logger('get_pokemon')->error(' No se pudo ejecutar el query: <pre>@error</pre>', [
                    '@error' => $ex->getMessage(),
                ]);
            return $response;
        }

        return $response;
    }

    /**
     * get_list_pokemon.
     *
     * @param string $id
     *
     * @return array
     *
     * pokemon by ID, with details
     */
    public function get_pokemon_details($id){

        $response = array();

        try {
            $request = $this->client->get($this->end_point.'/pokemon/'.$id);

            $pokemon = json_decode($request->getBody(),TRUE);

            $response['id'] = $id;
            $response['name'] = $pokemon['name'];
            $response['name'] =  $pokemon['sprites']['front_default'];

        }catch (\Exception $ex) {
            $this->drupal->logger('get_pokemon')->error(' No se pudo ejecutar el query: <pre>@error</pre>', [
                '@error' => $ex->getMessage(),
            ]);
            return $response;
        }



        return $response;
    }

    /**
     * get_list_pokemon.
     *
     * @param string $id
     *
     * @return array
     *
     * pokemon by ID, with abilities
     */
    public function get_pokemon_abilities($id){

        $response = array();

        try{
            $request = $this->client->get($this->end_point.'/pokemon/'.$id);

            $pokemon = json_decode($request->getBody(),TRUE);

            $response['id'] = $id;
            $response['name'] = $pokemon['name'];
            $response['image'] =  $pokemon['sprites']['front_default'];
            $response['base_experience'] =  $pokemon['base_experience'];
            foreach ($pokemon['stats'] as $value){
                $response['stats'] = $response['stats'].$value['stat']['name'].' ';
            }
            foreach ($pokemon['abilities'] as $value){
                $response['ability'] = $response['ability'].$value['ability']['name'].' ';
            }
            $response['height'] =  $pokemon['height'];
        }catch (\Exception $ex) {
            $this->drupal->logger('get_pokemon')->error(' No se pudo ejecutar el query: <pre>@error</pre>', [
                '@error' => $ex->getMessage(),
            ]);
            return $response;
        }

        return $response;
    }
}
