<?php

namespace Drupal\get_pokemon\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
/*
    Class: ListPokemonForm

    Form list pokemon and list favorites
*/
class ListPokemonForm extends FormBase{


    private $drupal;
    private $config;

    public function __construct($drupal = null)
    {
        $this->drupal = $drupal;
        if (! is_object($this->drupal)) {
            $this->drupal = new \Drupal();
        }
        $this->config = \Drupal::config('get_pokemon.settings');

    }

    /**
     * Returns a unique string identifying the form.
     *
     * @return string The unique string identifying the form.
     */
    public function getFormId()
    {
        return 'List_pokemon_form';
    }

    /**
     * Form constructor.
     *
     * @param array $form
     *   An associative array containing the structure of the form.
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     *   The current state of the form.
     *
     * @return array
     *   The form structure.
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $defaultValue = $_SESSION['List_pokemon_form'];

        $form['content'] = [
            '#type' => 'fieldset',
            '#title' => t('Poke Api')
        ];

        $form['content']['pokemon_name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('name of pokemon'),
            '#default_value' => isset($defaultValue['pokemon_name']) ? $defaultValue['pokemon_name'] : '',
            '#size' => 35
        ];

        $form['content']['search'] = [
            '#type' => 'submit',
            '#value' => $this->t('Search'),
            '#submit' => [
                '::searchQuery'
            ]
        ];

        $form['content']['list'] = [
            '#type' => 'fieldset',
            '#title' => t('pokemon list')
        ];

        $form['content']['list']['list_pokemon'] = array(
            '#tree' => TRUE,
            '#theme' => 'tigo_sim_card_preloaded_table_types',
            '#prefix' => '<div id="document-list-wrapper">',
            '#suffix' => '</div>'
        );

        //If there is an error message it is displayed
        if($defaultValue['message']){
            $form['content']['list']['list_pokemon']['message'] = array(
                '#type' => 'markup',
                '#markup' => '<div class="messages__wrapper layout-container"><div class="messages messages--error">'.$defaultValue['message'].'</div></div>',
            );

            $_SESSION['List_pokemon_form']['message']= NULL;
        }

        //get the service pokemones
        $pokemons = $this->drupal->service('get_pokemon.get_all_pokemon')->get_list_pokemon(@$defaultValue['pokemon_name']);

        //get pokemons in the DB
        $db = $this->drupal->database();
        $query = $db->select('pokemon','p');
        $query->fields('p', ['id']);
        $result = $query->execute()->fetchAll();
        $id_pokemons = array();

        foreach ($result as $value){
            array_push($id_pokemons,$value->id);
        }

        //an item is created for each pokemon
        foreach ($pokemons as $key => $item){
            $form['content']['list']['list_pokemon'][$item['id']] = [
                '#type' => 'container',
                '#attributes' => ['class' => 'item'],
            ];

            //if the pokemon is already in the BD do not show the "ad to favorite" button
            if(in_array($item['id'], $id_pokemons)){
                $form['content']['list']['list_pokemon'][$item['id']]['select'] = [
                    '#type' => 'submit',
                    '#name' => $item['id'],
                    '#value' => $this->t('Add to favorite'),
                    '#attributes' => ['disabled' => 'disabled'],
                    '#submit' => [
                        '::add_to_favorite'
                    ],
                    '#ajax' => [
                        'callback' => '::add_to_favorite_callback',
                        'event' => 'click'
                    ]
                ];
            }else{
                $form['content']['list']['list_pokemon'][$item['id']]['select'] = [
                    '#type' => 'submit',
                    '#name' => $item['id'],
                    '#value' => $this->t('Add to favorite'),
                    '#submit' => [
                        '::add_to_favorite'
                    ],
                    '#ajax' => [
                        'callback' => '::add_to_favorite_callback',
                        'event' => 'click'
                    ]
                ];
            }

            $form['content']['list']['list_pokemon'][$item['id']]['markup'] = array(
                '#type' => 'markup',
                '#markup' => '<div><h2>'.$item['name'].'</h2><img src="'.$item['sprites']['front_default'].'"></div>',
            );

            $form['content']['list']['list_pokemon'][$item['id']]['id'] = array(
                '#type' => 'hidden',
                '#default_value' => empty($item['id']) ? '' : $item['id']
            );
            $form['content']['list']['list_pokemon'][$item['id']]['name'] = array(
                '#type' => 'hidden',
                '#default_value' => empty($item['name']) ? '' : $item['name']
            );
            $form['content']['list']['list_pokemon'][$item['id']]['image'] = array(
                '#type' => 'hidden',
                '#default_value' => empty($item['sprites']['front_default']) ? '' : $item['sprites']['front_default']
            );
        }


        //header table of favorites
        $header = array(
            array(
                'data' => $this->t('ID'),
                'field' => 'id',
                'sort' => 'asc'
            ),
            array(
                'data' => $this->t('Name'),
                'field' => 'name',
                'sort' => 'asc'
            ),
            array(
                'data' => $this->t('base experience'),
                'field' => 'base_experience',
                'sort' => 'asc'
            ),
            array(
                'data' => $this->t('stats'),
                'field' => 'stats',
                'sort' => 'asc'
            ),
            array(
                'data' => $this->t('abilities'),
                'field' => 'ability',
                'sort' => 'asc'
            ),
            array(
                'data' => $this->t('height'),
                'field' => 'height',
                'sort' => 'asc'
            ),
            array(
                'data' => $this->t('Image')
            ),
            array(
                'data' => $this->t('Delete')
            )
        );

        $db = $this->drupal->database();
        $query = $db->select('pokemon','p');
        $query->fields('p');

        // The actual action of sorting the rows is here.
        $table_sort = $query->extend('Drupal\Core\Database\Query\TableSortExtender')->orderByHeader($header);

        $pager = $table_sort->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(10);

        $result = $pager->execute();

        // Populate the rows.
        $rows = array();


        //rows of favorites table from DB
        foreach($result as $row) {
            $url_delete = Url::fromRoute('get_pokemon.remove', ['action' => 'delete', 'id' => $row->id]);



            $rows[] = array(
                'data' => array(
                    'id' => $row->id,
                    'name' => $row->name,
                    'base_experience' => $row->base_experience,
                    'ability' => $row->ability,
                    'stats' => $row->stats,
                    'height' => $row->height,
                    'image' => array(
                        'data' => array(
                            '#markup' => '<img src="'.$row->image.'">',
                        ),
                        'class' => 'burro',
                    ),

                    'delete' => $this->l('Delete', $url_delete)
                )
            );

        }

        $form['favorites'] = array(
            '#type' => 'fieldset',
            '#title' => t('Favoritos'),
            '#open' => TRUE,
        );

        $form['favorites']['favorite_list'] = [
            '#tree' => TRUE,
            '#title' => t('Favorite pokemon'),
            '#prefix' => '<div id="document-favorites-wrapper">',
            '#suffix' => '</div>'
        ];



        if($defaultValue['message']){
            $form['favorites']['favorite_list']['message'] = array(
                '#type' => 'markup',
                '#markup' => '<div class="messages__wrapper layout-container"><div class="messages messages--error">'.$defaultValue['message'].'</div></div>',
            );

            $_SESSION['List_pokemon_form']['message'] = NULL;
        }



        $form['favorites']['favorite_list']['table'] = array(
            '#theme' => 'table',
            '#header' => $header,
            '#rows' => $rows,
            '#empty' => t('No se encontraron resultados con los parametros de busqueda.'),

        );



        return $form;
    }

    /**
     *
     * @param array $form
     * @param FormStateInterface $form_state
     */
    public function searchQuery(array $form, FormStateInterface $form_state)
    {
        $_SESSION['List_pokemon_form'] = [
            'pokemon_name' => $form_state->getValue('pokemon_name')
        ];
    }
    /**
     *
     * @param array $form
     * @param FormStateInterface $form_state
     *
     *  add to favorite a pokemon
     */
    public function add_to_favorite(array $form, FormStateInterface $form_state){
        $triggeringElement = $form_state->getTriggeringElement();
        $items = $form_state->getValue('list_pokemon');
        foreach ($items as $key => $var) {
            if ($triggeringElement['#name'] == $key) {

                if (! empty($items[$key]['id'])) {

                    try {
                        $db = $this->drupal->database();
                        $query = $db->select('pokemon','p');
                        $query->fields('p');
                        $result = $query->countQuery()->execute()->fetchField();

                        $num_max = $this->config->get('number_favorities') == NULL ? 10 : $this->config->get('number_favorities');

                        //if there are 10 pokemon in the DB , show the error
                        if(intval($result) >= intval($num_max)){
                            $_SESSION['List_pokemon_form']['message'] = 'You already have 10 pokemon in favorites, delete one';

                        }else{
                            $pokemon = $this->drupal->service('get_pokemon.get_all_pokemon')->get_pokemon_abilities(@$var['id']);
                            $connection = $this->drupal->database();
                            $result = $connection->insert('pokemon')
                                ->fields([
                                    'id' => $pokemon['id'],
                                    'name' => $pokemon['name'],
                                    'image' => $pokemon['image'],
                                    'base_experience' => $pokemon['base_experience'],
                                    'stats' => $pokemon['stats'],
                                    'ability' => $pokemon['ability'],
                                    'height' => $pokemon['height']
                                ])
                                ->execute();
                        }

                    } catch (\Exception $ex) {
                        $this->drupal->logger('get_pokemon')->error(' No se pudo ejecutar el query: <pre>@error</pre>', [
                            '@error' => $ex->getMessage(),
                        ]);
                    }
                }
            }
        }
        $form_state->setRebuild(TRUE);
    }

    /**
     *
     * @param array $form
     * @param FormStateInterface $form_state
     *
     *  callback ajax submit form to add favorites
     */
    public function add_to_favorite_callback(array $form, FormStateInterface &$form_state)
    {
        $response = new AjaxResponse();
        $content = $this->drupal->service('renderer')->render($form['favorites']['favorite_list']);
        $content2 = $this->drupal->service('renderer')->render($form['content']['list']['list_pokemon']);
        $response->addCommand(new ReplaceCommand('#document-favorites-wrapper', $content));
        $response->addCommand(new ReplaceCommand('#document-list-wrapper', $content2));
        return $response;
    }
    public function submitForm(array &$form, FormStateInterface $form_state)
    {}
}
