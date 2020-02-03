<?php
namespace Drupal\get_pokemon\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
/*
    Class: AdminGetPokemonForm

    admin all pokemons functions
*/
class AdminGetPokemonForm extends ConfigFormBase
{

    /**
     * Returns a unique string identifying the form.
     *
     * @return string The unique string identifying the form.
     */
    public function getFormId()
    {
        return 'get_pokemon_admin_form';
    }

    /**
     * Returns a unique string identifying the form.
     *
     * @return array config Names
     */
    protected function getEditableConfigNames()
    {
        return [
            'get_pokemon.settings'
        ];
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
        $config = $this->config('get_pokemon.settings');

        $form['content'] = [
            '#type' => 'fieldset',
            '#title' => t('config get pokemon')
        ];

        $form['content']['number_favorities'] = [
            '#type' => 'number',
            '#title' => $this->t('max number favorities'),
            '#default_value' => $config->get('number_favorities')
        ];

        $form['content']['end_point'] = [
            '#type' => 'textfield',
            '#title' => $this->t('End point pokeApi'),
            '#default_value' => $config->get('end_point')
        ];

        return parent::buildForm($form, $form_state);
    }


    /**
     *
     * @param array $form
     * @param FormStateInterface $form_state
     *
     *  submit form and add config
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        \Drupal::configFactory()->getEditable('get_pokemon.settings')
            ->set('number_favorities', $form_state->getValue('number_favorities'))
            ->set('end_point', $form_state->getValue('end_point'))
            ->save();
        parent::submitForm($form, $form_state);
    }
}
