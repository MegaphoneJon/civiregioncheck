#!/usr/bin/php
<?php

use League\Csv\Reader;
use League\Csv\Statement;

// Die if we can't find Civi.
if (!isset($argv[1])) {
  echo "You must provide a path to a webroot of a CMS with CiviCRM installed.\n";
  echo "Usage: civiregioncheck /path/to/webroot\n";
  die;
}
$cwd = getcwd();
// Perform CiviCRM bootstrap
// phpcs:ignore
eval(`cv --cwd=$argv[1] php:boot`);

// Get Civi countries.
$civiCountries = (array) \Civi\Api4\Country::get(FALSE)
  ->addSelect('name', 'iso_code')
  ->execute()
  ->indexBy('iso_code');

// Handle special cases Serbia and Montenegro (dissolved) and Kosovo (temporary code)
unset($civiCountries['XK']);
unset($civiCountries['CS']);

// Build CSV country array.
$csv = Reader::createFromPath($cwd . '/data/countries.csv', 'r');
$csv->setHeaderOffset(0);
$query = Statement::create();
$rows = $query->process($csv);
foreach ($rows as $row) {
  $csvCountries[$row['alpha-2']] = $row;
}

// Generate country comparison.
echo "**Country discrepancies**\n";
echo "ISO 3166-1, CiviCRM\n";
foreach ($csvCountries as $isoCode => $csvCountry) {
  if ($civiCountries[$isoCode]) {
    if ($csvCountry['name'] !== $civiCountries[$isoCode]['name']) {
      echo "\"{$csvCountry['name']}\", \"{$civiCountries[$isoCode]['name']}\"\n";
    }
    unset($csvCountries[$isoCode]);
    unset($civiCountries[$isoCode]);
  }
}
echo "\n";
foreach ($csvCountries as $isoCode => $csvCountry) {
  echo "Not in Civi: {$csvCountry['name']} (ISO Code $isoCode)\n";
}

foreach ($civiCountries as $isoCode => $civiCountry) {
  echo "Not in ISO-3166-1: {$civiCountry['name']} (ISO Code $isoCode)\n";
}

// REGION COMPARISON
// Build Civi region data.
$civiStateProvinces = (array) \Civi\Api4\StateProvince::get(FALSE)
  ->addSelect('name', 'abbreviation', 'country.iso_code')
  ->addJoin('Country AS country', 'INNER')
  ->execute();

foreach ($civiStateProvinces as $civiStateProvince) {
  $civiRegions[$civiStateProvince['country.iso_code']][$civiStateProvince['name']] = $civiStateProvince['abbreviation'];
}

// Build CSV region array.
$csv = Reader::createFromPath($cwd . '/data/IP2LOCATION-ISO3166-2.CSV', 'r');
$csv->setHeaderOffset(0);
$query = Statement::create();
$rows = $query->process($csv);
foreach ($rows as $row) {
  [, $abbreviation] = explode('-', $row['code']);
  $csvRegions[$row['country_code']][$row['subdivision_name']] = $abbreviation;
}
ksort($civiRegions);
ksort($csvRegions);
// Generate region comparison.
echo "**Region discrepancies**\n";
echo "**Mismatched abbreviations**\n";
echo "ISO Code, Name, ISO 3166-2 abbreviation, CiviCRM abbreviation\n";
foreach ($civiRegions as $isoCode => $civiRegion) {
  foreach ($civiRegion as $civiState => $civiAbbreviation) {
    if ($csvRegions[$isoCode][$civiState] ?? FALSE) {
      if ($csvRegions[$isoCode][$civiState] !== $civiAbbreviation) {
        echo "$isoCode, \"$civiState\", {$csvRegions[$isoCode][$civiState]}, $civiAbbreviation,\n";
      }
      unset($civiRegions[$isoCode][$civiRegion['name']]);
      unset($csvRegion[$isoCode][$civiRegion['name']]);
    }
  }
  // if ($csvRegion[$isoCode])
}
