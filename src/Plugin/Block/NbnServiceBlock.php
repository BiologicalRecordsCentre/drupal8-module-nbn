<?php

namespace Drupal\nbn\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\nbn\Controller\NBNClientController;
/**
 * @Block (
 *   id = "nbnservice_block",
 *   admin_label =  @Translation("NBN Service"),
 *   category = @Translation("This block is to get access of NBN Datasets"),
 * )
 */

class NbnServiceBlock extends BlockBase {

  public function build() {
    //return \Drupal::formBuilder()->getForm('Drupal\nbn\Form\NbnServiceBlockForm');
    $provider = $this->configuration['nbn_dataid'];
    // Ensure organisation is an integer with prefix dp
    $prov_prefix = substr($provider, 0, 2);
    $prov_int = substr($provider, 2);

    if ($prov_prefix !== 'dp' || !ctype_digit($prov_int)) {
      // An incorrect substitution string resolves to '' but so does a correct
      // substitution string where no value is supplied. Therefore, it is not
      // possible to report an error.
      return;
    }
    else {
      // Request data from NBN
      $nbn_client = new NBNClientController;
      $data = $nbn_client->GetProviderResources($provider);
    }

    $conf = $this->configuration;
    //$link_url = $this->configuration['options']['link_url'];

    //foreach($data as $key => $row) {
    //  $data[$key]['name'] = '<a href="$link_url{$row["uid"]}">{$row["name"]}</a>';
    //}

    $row_classes = array();

    // Add default class
    for($i = 0; $i < count($data); $i++) {
      $row_classes[$i] = 'nbn-row';
    }
    // Add striping classes
    if ($this->configuration['options']['striping']) {
      for($i = 0; $i < count($data); $i++) {
        $row_classes[$i] .= $i%2 ? ' nbn-even' : ' nbn-odd';
      }
      $row_classes[0] .= ' nbn-first';
      $row_classes[count($data) - 1] .= ' nbn-last';
    }

    $field_classes = array();

    foreach($conf['fields'] as $field => $enabled) {
      // Loop through fields
      if($enabled) {
        // Add a default class for each enabled field.
        $field_classes[$field] = 'nbn-field';
        if ($conf['options']['field_class']) {
          // Add field classes
          $field_classes[$field] .= 'nbn-' . strtolower($field);
        }
      }
    }

    $wrapper_classes = 'nbn-content ';
    if (isset($conf['options']['wrapper_class'])) {
      $wrapper_classes .= $conf['options']['wrapper_class'];
    }

    //Add heading of the page from block.
    $markup = '<h1>' . $this->configuration['label'] .'</h1>';

    // Ensure some data has been returned.

    if (empty($data)) {
      return;
    }
    else {
      // Theme data
      $variables = [
        '#data' => $data,
        '#conf' => $this->configuration,
        '#wrapper_classes' => $wrapper_classes,
        '#row_classes' => $row_classes,
        '#field_classes' => $field_classes,
      ];
      switch ($this->configuration['options']['format']) {
        case 'table':
          $nbn_theme = array_merge(['#theme' => 'nbn_table'],$variables);
          $markup .= \Drupal::service('renderer')->render($nbn_theme);
          break;
        case 'html_list':
          $nbn_theme = array_merge(['#theme' => 'nbn_html_list'],$variables);
          $markup .= \Drupal::service('renderer')->render($nbn_theme);
          break;
        default:
          $nbn_theme = array_merge(['#theme' => 'nbn_unformatted'],$variables);
          $markup .= \Drupal::service('renderer')->render($nbn_theme);
          break;
      }
    }

    return array(
      '#type' => 'markup',
      '#markup' => $markup,
    );
  }

  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access content');
  }

  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $form['nbn_dataid'] = array(
      '#type' => 'textfield',
      '#title' => t('NBN data provider id'),
      '#description' => t('You can determine the id of an organisation from its url at https://registry.nbnatlas.org/. Either insert a dp number or a %substitution token for a field.'),
      '#default_value' => isset($config['nbn_dataid']) ? $config['nbn_dataid'] : 'dp77',
    );

    $form['fields'] = array(
      '#type' => 'fieldset',
      '#title' => t('Fields to display')
    );
    // Field names match those in the NBN response
    $form['fields']['name'] = array(
      '#type' => 'checkbox',
      '#title' => t('Data resource name'),
      '#default_value' => isset($config['name']) ? $config['name'] : 1,
    );
    $form['fields']['uri'] = array(
      '#type' => 'checkbox',
      '#title' => t('Uri of web service for the data resource'),
      '#default_value' => isset($config['uri']) ? $config['uri'] : 0,
    );
    $form['fields']['uid'] = array(
      '#type' => 'checkbox',
      '#title' => t('Unique id used publicly'),
      '#default_value' => isset($config['uid']) ? $config['uid'] : 0,
    );
    $form['fields']['id'] = array(
      '#type' => 'checkbox',
      '#title' => t('Some other id'),
      '#default_value' => isset($config['id']) ? $config['id'] : 0,
    );
    $form['options'] = array(
      '#type' => 'fieldset',
      '#title' => t('Output options')
    );
    $form['options']['name_link'] = array(
      '#type' => 'checkbox',
      '#title' => t('Display name as link to web page'),
      '#default_value' => isset($config['name_link']) ? $config['name_link'] : 1,
    );
    $form['options']['link_url'] = array(
      '#type' => 'textfield',
      '#title' => t('Base url of Atlas pages'),
      '#description' => t('Url to which uid will be appended in order to create link to data resource page.'),
      '#default_value' => isset($config['link_url']) ?
      $config['link_url'] : 'https://registry.nbnatlas.org/public/show/',
    );
    $form['options']['format'] = array(
      '#type' => 'radios',
      '#title' => t('Ouput format'),
      '#default_value' => isset($config['format']) ? $config['format'] : 'unformatted',
      '#options' => array(
          'unformatted' => t('Unformatted list'),
          'html_list' => t('Html list'),
          'table' => t('Table'),
      ),
    );
    $form['options']['wrapper_class'] = array(
      '#type' => 'textfield',
      '#title' => t('Wrapper class'),
      '#description' => t('The css class of the outermost html element.'),
      '#default_value' => isset($config['wrapper_class']) ? $config['wrapper_class'] : '',
    );
    $form['options']['field_class'] = array(
      '#type' => 'checkbox',
      '#title' => t('Add field classes'),
      '#description' => t('Add css classes to each field.'),
      '#default_value' => isset($config['field_class']) ? $config['field_class'] : 1,
    );
    $form['options']['striping'] = array(
      '#type' => 'checkbox',
      '#title' => t('Add striping classes'),
      '#description' => t('Add css classes of odd and even for striping and first/last.'),
      '#default_value' => isset($config['striping']) ? $config['striping'] : 1,
    );

    return $form;
  }

  public function blockSubmit($form, FormStateInterface $form_state) {
    //$this->setConfigurationValue('nbnservice_block_settings', $form_state->getValue('nbnservice_block_settings'));
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['nbn_dataid'] = $values['nbn_dataid'];
    $this->configuration['fields']['name'] = $values['fields']['name'];
    $this->configuration['fields']['uri'] = $values['fields']['uri'];
    $this->configuration['fields']['uid'] = $values['fields']['uid'];
    $this->configuration['fields']['id'] = $values['fields']['id'];
    $this->configuration['options']['name_link'] = $values['options']['name_link'];
    $this->configuration['options']['link_url'] = $values['options']['link_url'];
    $this->configuration['options']['format'] = $values['options']['format'];
    $this->configuration['options']['wrapper_class'] = $values['options']['wrapper_class'];
    $this->configuration['options']['field_class'] = $values['options']['field_class'];
    $this->configuration['options']['striping'] = $values['options']['striping'];
  }

}