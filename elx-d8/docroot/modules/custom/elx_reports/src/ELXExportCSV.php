<?php

namespace Drupal\elx_reports;

/**
 * Export CSV.
 *
 * Provides helper function.
 */
abstract class ELXExportCSV {

  /**
   * Generate CSV helper function.
   *
   * @param mixed $data
   *   Data to create csv.
   * @param string $file
   *   File name.
   * @param string $mode
   *   Mode to open.
   *
   * @throws \Exception
   */
  protected function generateCsv($data, $file, $mode = 'w') {
    $stream = fopen($file, $mode);
    // Header will be the first row.
    fputcsv($stream, array_values($this->getHeader()));
    // Columns and order of columns will be given by the $csv_header.
    foreach ($data as $record) {
      $row = [];
      foreach ($this->getHeader() as $field => $title) {
        if (!property_exists($record, $field)) {
          array_push($row, NULL);
        }
        else {
          array_push($row, $record->{$field});
        }
      }
      fputcsv($stream, $row);
    }
    fclose($stream);
  }

  /**
   * Get the csv header and columns order.
   */
  abstract protected function getHeader();

}
