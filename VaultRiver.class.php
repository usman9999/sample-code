<?php


class VaultRiver extends River {
  /**
   * Issue to filter the river
   * @var string
   */
  private $issue;

  /**
   * Sections to show in this river
   * @var Array
   */
  private $sections;

  /**
   * Sections to hide from this river
   * @var Array
   */
  private $not_sections;

  /**
   * Filter to only uncurated or only curated
   * @var bool
   */
  private $is_uncurated;

  /**
   * @var string
   */
  private $start_date;

  /**
   * @var end
   */
  private $end_date;

  /**
   * @var bool
   */
  private $unique_issues;

  /**
   * Whether to automatically order the query
   * @var bool
   */
  private $no_sort;

  /**
   * Show only results from an issue with issue #
   * @param string $issue issue number
   * @return VaultRiver $this
   */
  public function inIssue($issue) {
    $this->issue = $issue;
    return $this;
  }

  /**
   * Hide all cover articles from the result set
   * @return VaultRiver $this
   */
  public function noCovers() {
    return $this->notInSections(si_vault_core_get_cover_sections());
  }


  /**
   * Don't show articles from these sections
   * @param Array $sections
   * @return VaultRiver
   */
  public function notInSections(Array $sections) {
    $this->not_sections = $sections;
    return $this;
  }

  /**
   * Limit articles to these sections
   * @param array $sections
   * @return VaultRiver
   */
  public function inSections(Array $sections) {
    $this->sections = $sections;
    return $this;
  }


  public function uniqueIssues() {
    $this->unique_issues = true;
  }

  /**
   * Show only cover articles in the result set
   * @return VaultRiver $this
   */
  public function onlyCovers() {
    return $this->inSections(si_vault_core_get_cover_sections());
  }

  public function betweenDates($start_date, $end_date = NULL) {
    $expected_format = 'Y-m-d H:i:s';
    if (is_numeric($start_date)) {
      $start_date = date($expected_format, $start_date);
    }
    if (is_numeric($end_date)) {
      $end_date = date($expected_format, $end_date);
    }

    $this->start_date = $start_date;
    $this->end_date = $end_date;
    return $this;
  }

  /**
   * Show only cover articles in the result set
   * @return VaultRiver $this
   */
  public function fromDecade($decade = 1950) {
    $decade_floor = $decade . '-01-01 00:00:00';
    $decade_ceiling = ($decade + 10) . '-01-01 00:00:00';
    return $this->betweenDates($decade_floor, $decade_ceiling);
  }

  /**
   * Show only cover articles in the result set
   * @return VaultRiver $this
   */
  public function fromYear($year = 1954) {
    $year_floor = $year . '-01-01 00:00:00';
    $year_ceiling = ($year + 1) . '-01-01 00:00:00';
    return $this->betweenDates($year_floor, $year_ceiling);
  }

  public function noSort() {
    $this->no_sort = TRUE;
    return $this;
  }

  public function withUncurated() {
    $this->is_uncurated = TRUE;
    return $this;
  }

  protected function getInfo() {
    $parent = parent::getInfo();

    $self = array(
      'issue' => $this->issue,
      'uncurated' => $this->is_uncurated,
      'cover_start_date' => $this->start_date,
      'cover_end_date' => $this->end_date,
      'vault_query' => TRUE,
      'unique_issues' => $this->unique_issues,
    );

    if ($this->no_sort) {
      $self['no_sort'] = TRUE;
    }

    if (!empty($this->sections)) {
      $self['vault_sections'] = $this->sections;
    }

    if (!empty($this->not_sections)) {
      $self['vault_not_sections'] = $this->not_sections;
    }

    return array_merge($parent, $self);
  }

  /**
   * @param String $name
   * @return VaultRiver
   */
  public static function load($name) {
    $river = new VaultRiver($name);
    $river = $river->withContentTypes(['vault_article']);
    return $river;
  }
}