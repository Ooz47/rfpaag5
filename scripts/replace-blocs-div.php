<?php

/**
 * @file
 * Drush script to dry-run or apply replacing
 * <div class="divwysiwyg…"> → <p class="divwysiwyg…">,
 * with enhanced logging.
 *
 * Usage:
 *   # Dry-run (compte seulement) :
 *   drush scr scripts/replace-blocs.php
 *
 *   # Pour appliquer la mise à jour :
 *   drush scr scripts/replace-blocs.php --apply
 */

// Ajout de l'autoload Drupal si besoin
if (!class_exists('\Drupal')) {
  require_once 'vendor/autoload.php';
}

// use Drupal\Core\Database\Query\Expression;

// Lecture du flag --apply.
$apply = in_array('--apply', $_SERVER['argv'], TRUE);

// Le regex et la replacement placés dans des variables pour un log clair.
$pattern     = '(?s)<div([^>]*)class="([^"]*divwysiwyg[^"]*)"([^>]*)>(.*?)</div>';
$replacement = '<p\\1class="\\2"\\3>\\4</p>';

// Entête de log.
echo str_repeat('=', 60) . "\n";
echo ($apply ? "MODE APPLY" : "MODE DRY-RUN") . " — Remplacement de <div class=\"divwysiwyg\"> par <p class=\"divwysiwyg\">\n";
echo "Pattern  : {$pattern}\n";
echo "Remplace : {$replacement}\n";
echo str_repeat('=', 60) . "\n\n";

// Récupération de la connexion DB.
$db = \Drupal::database();
$db_name = $db->getConnectionOptions()['database'];

// Étape 1 : lister tous les champs *_format.
$result = $db->query("
  SELECT table_name, column_name
  FROM information_schema.columns
  WHERE table_schema = :db
    AND column_name LIKE '%\\_format'
", [':db' => $db_name]);


foreach ($result as $row) {
  $table      = $row->table_name;
  $col_format = $row->column_name;
  $col_value  = preg_replace('/_format$/', '_value', $col_format);

  if (!$db->schema()->fieldExists($table, $col_value)) {
    continue;
  }

  // Dry-run : on passe sur LIKE pour détecter.
  $count = $db->select($table, 't')
    ->condition($col_format, ['basic_html','full_html'], 'IN')
    ->condition($col_value, '%<div class="divwysiwyg%', 'LIKE')
    ->countQuery()
    ->execute()
    ->fetchField();

  if ($count == 0) {
    continue;
  }

  echo "[DRY-RUN] Table {$table} : {$count} ligne(s) détectée(s)\n";

  // **Prévisualisation** de 3 premiers cas
  $preview_query = $db->select($table, 't')
    ->fields('t', [$col_value])
    ->condition($col_format, ['basic_html','full_html'], 'IN')
    ->condition($col_value, '%<div class="divwysiwyg%', 'LIKE')
    ->range(0, 3)
    ->execute();

  while ($record = $preview_query->fetchField()) {
    // on isole un petit extrait autour du <div…>
    if (preg_match('#(.{0,30}<div class="divwysiwyg[^"]*"[^>]*>.*?</div>.{0,30})#s', $record, $m)) {
      $orig = $m[1];
    } else {
      // sinon on prend les 60 premiers caractères
      $orig = substr($record, 0, 60) . (strlen($record) > 60 ? '…' : '');
    }
    // on applique la même regex PHP que l'UPDATE
    $new = preg_replace(
      '#(?s)<div([^>]*)class="([^"]*divwysiwyg[^"]*)"([^>]*)>(.*?)</div>#',
      '<p\1class="\2"\3>\4</p>',
      $orig
    );
    echo "  • Preview:\n";
    echo "     - ORIG: “{$orig}”\n";
    echo "     - NEW : “{$new}”\n\n";
  }

  echo str_repeat('-', 60) . "\n";

  // En mode apply, on exécute…
  if ($apply) {
    $sql = "
      UPDATE `{$table}`
      SET `{$col_value}` = REGEXP_REPLACE(`{$col_value}`, :pattern, :replacement)
      WHERE `{$col_format}` IN ('basic_html', 'full_html')
        AND `{$col_value}` LIKE '%<div class=\"divwysiwyg%'
    ";
  
    $db->query($sql, [
      ':pattern' => '(?s)<div([^>]*)class="([^"]*divwysiwyg[^"]*)"([^>]*)>(.*?)</div>',
      ':replacement' => '<p\\1class="\\2"\\3>\\4</p>',
    ]);
  
    echo "[ APPLIED ] Table {$table} : {$count} ligne(s) modifiée(s) (estimation via COUNT)\n";
  }
  
}


echo "\n=== Script terminé ===\n";
