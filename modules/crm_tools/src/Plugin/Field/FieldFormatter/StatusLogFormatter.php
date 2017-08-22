<?php

namespace Drupal\decoupled_auth_crm_tools\Plugin\Field\FieldFormatter;

use Drupal\Component\Datetime\DateTimePlus;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the 'status_log_list' field formatter.
 *
 * @FieldFormatter(
 *   id = "status_log_list",
 *   label = @Translation("Status Log (list)"),
 *   field_types = {
 *     "status_log"
 *   }
 * )
 */
class StatusLogFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings']
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    // @todo Add human readable labels.
    $elements = [];
    foreach ($items as $delta => $item) {
      /* @var \Drupal\decoupled_auth_crm_tools\Plugin\Field\FieldType\StatusLog $item */
      $values = $item->getValue();
      if (is_array($values) && !empty($values['value'])) {

        $elements[$delta]['value'] = [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#attributes' => ['class' => []],
          '#value' => $this->t('@time: Status was changed from %status_old to %status_new by @username.', [
            '%status_new' => $values['value'],
            '%status_old' => $values['previous'],
            '@time' => DateTimePlus::createFromTimestamp($values['timestamp'])->format('Y-m-d H:i:s'),
            '@username' => User::load($values['uid'])->label(),
          ]),
        ];

      }
    }

    return $elements;
  }

}
