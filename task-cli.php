<?php
require 'classes.php';

if (!isset($argv[1]))
  return;
$ds = new JsonDataSource();
switch ($argv[1]) {
  case 'add':
    if (!isset($argv[2]) || $argv[2] == '') {
      fwrite(STDERR, 'Error: Task not added. Missing arguments' . PHP_EOL);
      break;
    }
    $id = $ds->add($argv[2]);
    echo 'Task added successfully (ID: ', $id, ')', PHP_EOL;
    break;
  case 'update':
    if (!isset($argv[3]) || $argv[2] == '' || $argv[3] == '') {
      fwrite(STDERR, 'Error: Task not updated. Missing arguments' . PHP_EOL);
      break;
    }
    if (!is_numeric($argv[2])) {
      fwrite(STDERR, 'Error: Task not updated. The first argument is not numeric' . PHP_EOL);
      break;
    }
    $ds->update($argv[2], $argv[3]);
    break;
  case 'delete':
    if (!isset($argv[2]) || $argv[2] == '') {
      fwrite(STDERR, 'Error: Task not deleted. Missing arguments' . PHP_EOL);
      break;
    }
    if (!is_numeric($argv[2])) {
      fwrite(STDERR, 'Error: Task not deleted. The first argument is not numeric' . PHP_EOL);
      break;
    }
    $ds->delete($argv[2]);
    break;
  case 'mark-in-progress':
    if (!isset($argv[2]) || $argv[2] == '') {
      fwrite(STDERR, 'Error: Task not marked. Missing arguments' . PHP_EOL);
      break;
    }
    if (!is_numeric($argv[2])) {
      fwrite(STDERR, 'Error: Task not marked. The argument is not numeric' . PHP_EOL);
      break;
    }
    $ds->markInProgress($argv[2]);
    break;
  case 'mark-done':
    if (!isset($argv[2]) || $argv[2] == '') {
      fwrite(STDERR, 'Error: Task not marked. Missing arguments' . PHP_EOL);
      break;
    }
    if (!is_numeric($argv[2])) {
      fwrite(STDERR, 'Error: Task not marked. The argument is not numeric' . PHP_EOL);
      break;
    }
    $ds->markDone($argv[2]);
    break;
  case 'list':
    $tasks = array();
    if (!isset($argv[2]) || $argv[2] == '') {
      $tasks = $ds->list();
    } else {
      switch ($argv[2]) {
        case 'all':
          $lk = ListKind::All;
          break;
        case 'todo':
          $lk = ListKind::Todo;
          break;
        case 'done':
          $lk = ListKind::Done;
          break;
        case 'in-progress':
          $lk = ListKind::InProgress;
          break;
      }
      $tasks = $ds->list($lk);
    }
    print_r($tasks);
    break;
}
