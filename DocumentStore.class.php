<?php

/**
 * @file
 *
 * DocumentStore Class
 *
 * Faster documents.
 */
namespace Timeinc\Si\DocumentStore;

require_once drupal_get_path('module', 'ti_content_api') . '/inc/format.inc.php';

use Timeinc\Si\ContentApi\format;

class DocumentStore {

  public static function version($version) {
    return new DocumentStore($version);
  }

  function __construct($version = 'v2') {
    return $this->version = $version;
  }

  public function get($id, $options, $assoc = false) {
    $options = $this->removeOptions($options);
    $key = $this->generateKey($id, $options);
    $result = $this->select($key);
    if (isset($result->json)) {
      $document = json_decode($result->json, true);
      $document['$stored'] = true;
      return $document;
    } else {
      return null;
    }
  }

  public function set($id, $options, $document) {
    $options = $this->removeOptions($options);
    if (!$this->isValid($id, $options, $document)) {
      return false;
    }

    $key = $this->generateKey($id, $options);
    return $this->insert($key, $id, $options, $document);
  }

  public function clearDocumentWithId($id) {
    db_delete(ti_document_store_table_name())
      ->condition('nid', $id)
      ->execute();
    return true;
  }

  private function generateKey($id, $options) {
    // Removing ID will avoid duplicate entry for path vs ID.
    unset($options['get']['id']);
    // Sort the outer array
    ksort($options);
    // Sort each inner array
    foreach($options as &$option) {
      ksort($option);
    }
    return md5($id . $this->version . serialize($options));
  }

  private function isValid($id, $options, $document) {
    return is_numeric($id) &&
      is_array($options) &&
      is_array($document) &&
      $document['id'] === $id &&
      isset($document['type']);
  }

  private function removeOptions($options) {
    unset($options['get']['path']);
    unset($options['get']['custom_path']);
    unset($options['get']['_enable_logging']);
    unset($options['get']['q']);
    return $options;
  }

  private function select($key) {
    $result = db_select(ti_document_store_table_name(), 't')
      ->fields('t', ['json'])
      ->condition('t.document_key', $key, '=')
      ->execute()
      ->fetch();

    return $result;
  }

  private function insert($key, $id, $options, $document) {
    $result = db_merge(ti_document_store_table_name())
      ->key(['document_key' => $key])
      ->fields([
        'document_key' => $key,
        'nid' => $id,
        'options' => json_encode($options),
        'version' => $this->version,
        'type' => $document['type'],
        'json' => json_encode($document),
      ])
      ->execute();

    return $result;
  }

}
