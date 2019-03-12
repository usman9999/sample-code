<?php
/**
 * @file
 *
 * RiverArrangement Class
 *
 * Pulls data from a river_arrangement node type and build tiles from it.
 */

class RiverArrangement implements Countable, RiverInterface
{

  // {Int | null} The nid of the river_arrangement node.
  public $nid;

  // {String | null} The name of the category.
  protected $category;

  // {Array} An array of tile objects.
  protected $tiles;

  // {WarTreatment | null} A war treatment tile, if one is required.
  protected $war_treatment;

  // {Array} Layout configuration options for various treatments.
  //    The keys are fields in drupal and the values are the various
  //    options for container nodes.
  protected static $layouts = array(
    'single' => array(
      'marquee_articles' => 'Marquee',
      'top_stories' => 'TopStories',
      'lily_pad_1_articles' => 'Lilypad',
      'lily_pad_2_articles' => 'Lilypad'
    ),

    'double' => array(
      'marquee_articles' => 'Marquee',
      'top_stories' => 'TopStories',
      'lily_pad_1_articles' => 'Lilypad',
      'lily_pad_2_articles' => 'Lilypad'
    ),

    'three' => array(
      'marquee_articles' => 'Marquee',
      'top_stories' => 'TopStories',
      'lily_pad_1_articles' => 'Lilypad',
      'lily_pad_2_articles' => 'Lilypad'
    ),

    'three_leftright' => array(
      'marquee_articles' => 'Marquee',
      'top_stories' => 'TopStories',
      'lily_pad_1_articles' => 'Lilypad',
      'lily_pad_2_articles' => 'Lilypad'
    ),

    'four' => array(
      'marquee_articles' => 'Marquee',
      'top_stories' => 'TopStories',
      'lily_pad_1_articles' => 'Lilypad',
      'lily_pad_2_articles' => 'Lilypad'
    ),

    'war_single_image' => array(
      'marquee_articles' => 'WarTreatment',
      'top_stories' => 'TopStories',
      'lily_pad_1_articles' => 'Lilypad',
      'lily_pad_2_articles' => 'Lilypad'
    ),

    'single_wide_leaderboard' => array(
      'marquee_articles' => 'Marquee',
      'top_stories' => 'TopStories',
      'lily_pad_1_articles' => 'Lilypad',
      'lily_pad_2_articles' => 'Lilypad'
    ),
  );

  // {Array} All of the fields to utilize from the river_arrangement node.
 protected static $arrangement_fields = array('marquee_articles','leaderboard_tiles', 'top_stories', 'priority_1_page_articles', 'lily_pad_1_articles', 'priority_2_page_articles', 'lily_pad_2_articles');

  /**
   * Constructor method. Called upon object instantiation.
   *
   * @see __construct
   * @param Int | Null $name
   * @param String $category
   */
  protected function constructor($nid = null, $category = null) {
    $this->nid = $nid;
    $this->category = $category;
    $this->tiles = array();
    $this->war_treatment = null;
 }

 /**
   * Set the arrangement_fields for a leaderboard tile (primary and wide)
   * @return $arrangement_fields
   *    Returns the current array of river arrangemen, started with the leaderboard tile
   */
  static function setArrangementFieldsLeaderboardOption() {
    self::$arrangement_fields = array('leaderboard_tiles', 'marquee_articles', 'top_stories', 'priority_1_page_articles', 'lily_pad_1_articles', 'priority_2_page_articles', 'lily_pad_2_articles');
  }

  /**
   * Set the arrangement's nid
   *
   * @param Int $nid
   * @return RiverArrangement
   *    Returns the current instance of RiverArrangement for method chaining.
   */
  public function withNid($nid) {
    if (is_integer($nid)) $this->nid = $nid;
    return $this;
  }

  /**
   * Set the arrangement's category.
   *
   * @param String $category
   * @return River
   *    Returns the current instance of RiverArrangement for method chaining.
   */
  public function withCategory($category) {
    if(is_string($category)) $this->category = $category;
    return $this;
  }

  /**
   * Get tiles for the river arrangement from a node.
   *
   * @return River
   *    Returns the current instance of RiverArrangement for method chaining.
   */
  public function flow() {

    // Get the associated river_arrangement node.
    $node = $this->getNode();

    // If we don't have a node, we can't continue.
    if (!is_object($node)) return $this;

    // Generate an array of tiles based on the fields in the node.
    $this->generateTiles($node);

    // Return the instance (for method chaining.)
    return $this;
  }

/* ======================
       Generate Tiles
    ====================== */

  /**
   * Get the associated river_arrangement node.
   *
   * @return StdClass $node | null
   */
  private function getNode() {

    // Either load the specified river_arrangement node...
    if (!is_null($this->nid)) {
      $node = node_load($this->nid);
    }

    // ...or query for one based on specified category.
    //   (pull the most recent river_arrangement node from the db.
    //   null here means we just get the latest node regardless of
    //   category.)
    else {
      $nid = RiverArrangementQuery::getArrangementNid($this->category);
      $this->nid = $nid;
      $node = node_load($nid);
    }

    return $node;
  }

  /**
   * Generate tiles based on river_arrangement fields.
   *
   * @param StdClass $node
   */
  private function generateTiles($node) {

    // Select which layout to use based on the layout field option.
    $layout = $this->selectLayout($node);
    // $arrangement_fields set for leaderboard tile as a wide and primary
    if ($layout == 'single_wide_leaderboard') {
      $this->setArrangementFieldsLeaderboardOption();
    }

    // Iterate through each mapping field of the river_arrangement node.
    foreach(self::$arrangement_fields as $field) {
      // Empty out array of nodes/reset type.
      $nodes = array(); $type = null;

      // Pull all of the nids out of a given node field in the arrangement.
      $nodes = RiverArrangementQuery::collectNodes($field, $node);

      if (!is_array($nodes)) {
        continue;
      }

      // Turn the list of nid's into a list of tiles.
      $tiles = TileFactory::buildNodeTilesFromArray($nodes);

      // Either we want to put all of these nodes in a container, like a
      // Marquee or War Treatment...
      if (array_key_exists($field, self::$layouts[$layout])) {

        // Get the type of container to build.
        $type = self::$layouts[$layout][$field];

        $this->addContainer($type, $tiles, $layout);
      }

      // ...or we just want to map them all to normal tiles.
      else {
        $this->addTiles($tiles);
      }
    }
  }

  /* ======================
     Tile Generation Helpers
      ====================== */

  /**
   * Private method to add tiles to the tiles array.
   *
   * @param Array $tiles
   *     An array of tile objects.
   */
  private function addTiles(array $tiles) {

    // @todo: validate array contains only Tiles.

    $this->tiles = array_merge($this->tiles, $tiles);
  }

  /**
   * Private method to add containers to the tiles array.
   *
   * @param String $type
   *    A name of the instance to create.
   * @param Array $tiles
   *    An array of NodeTiles to go into the container.
   */
  private function addContainer($type, $tiles, $variation = null) {

    $data = array('tiles' => $tiles);

    // Set a marquee variation.
    if ($variation) $data['variation'] = $variation;

    $container = Tile::load($type, $data);

    $this->addTiles(array($container));
  }

  /**
  * Select which layout to use based on the layout field option.
  *
  * @param StdClass $node
  * @return String
  */
  private function selectLayout($node) {

    // Pull the field value.
    $option = RiverQuery::fieldValue('marquee_layout', $node);

    // Remove the cruft.
    $option = str_replace(self::prefix('river_marquee_'), '', $option);

    return $option;
  }

  /* ======================
         Output Methods
      ====================== */

  /**
   * Get the number of items in the river arrangement.
   *
   * @see Countable Interface
   * @return Int
   */
  public function count() {
    return count($this->tiles);
  }

  /**
   * Print out info about the arrangement.
   *
   * @return String
   */
  public function toString() {
    return count($this->tiles);
  }

  /**
   * Return arrangement tiles in an array.
   *
   * @return Array
   */
  public function toArray() {
    return $this->tiles;
  }

  /**
   * Return info about the object as json.
   *
   * @return String
   */
  public function toJSON() {
    return json_encode(
      array_map(function($tile) {
        return $tile->toArray();
      }, $this->tiles)
    );
  }

  /**
   * Static helper function to apply a site-specific namespace to files.
   *
   * @param String $string
   * @return String
   */
  public static function prefix($string) {
    return $GLOBALS['ti_nsg_rivers_config']['river arrangement prefix'] . '_' . $string;
  }


  /* ======================
         Magic Methods
      ====================== */

  function __construct($name) {
    return $this->constructor($name);
  }

  function __toString() {
    // render.
  }

  function __get($property) {
    if ($property === 'id') {
      return $this->nid;
    }
  }

}
