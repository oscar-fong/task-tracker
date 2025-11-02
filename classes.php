<?php
enum ListKind
{
  case All;
  case InProgress;
  case Done;
  case Todo;
}

interface DataSource
{
  function add(string $description): int;
  function update(int $id, string $description);
  function delete(int $id);
  function markInProgress(int $id);
  function markDone(int $id);
  function list(ListKind $lk): array;
}

class SqliteDataSource implements DataSource
{
  private ?PDO $db;
  public function __construct()
  {
    $this->db = new PDO('sqlite:database.sqlite');
    $this->db->exec('CREATE TABLE IF NOT EXISTS tasks (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      description TEXT NOT NULL DEFAULT \'\',
      status TEXT CHECK ( status IN (\'todo\' , \'done\', \'in-progress\') ) NOT NULL DEFAULT \'todo\')');
  }
  function __destruct()
  {
    $this->db = null;
  }

  public function add(string $description): int
  {
    $query = 'INSERT INTO tasks (description) VALUES (?)';
    $st = $this->db->prepare($query);
    $st->execute([$description]);
    return $this->db->lastInsertId();
  }

  public function update(int $id, string $description)
  {
    $query = 'UPDATE tasks SET description = ? WHERE id = ?';
    $st = $this->db->prepare($query);
    $st->execute([$description, $id]);
  }
  public function delete(int $id)
  {
    $query = 'DELETE FROM tasks WHERE id = ?';
    $st = $this->db->prepare($query);
    $st->execute([$id]);
  }
  public function markDone(int $id)
  {
    $query = 'UPDATE tasks SET status = \'done\' WHERE id = ?';
    $st = $this->db->prepare($query);
    $st->execute([$id]);
  }
  public function markInProgress(int $id)
  {
    $query = 'UPDATE tasks SET status = \'in-progress\' WHERE id = ?';
    $st = $this->db->prepare($query);
    $st->execute([$id]);
  }
  public function list(ListKind $lk = ListKind::All): array
  {
    switch ($lk) {
      case ListKind::All:
        $query = 'SELECT id, description, status FROM tasks';
        break;
      case ListKind::InProgress:
        $query = 'SELECT id, description, status FROM tasks WHERE status = \'in-progress\'';
        break;
      case ListKind::Done:
        $query = 'SELECT id, description, status FROM tasks WHERE status = \'done\'';
        break;
    }
    $tasks = $this->db->query($query, PDO::FETCH_ASSOC)->fetchAll();
    return $tasks;
  }
}
class JsonDataSource implements DataSource
{
  private const PATH = 'tasks.json';
  private array $tasks;
  public function __construct()
  {
    if (!filesize(self::PATH)) {
      $handle = fopen(self::PATH, 'w');
      fwrite($handle, '[]');
      fclose($handle);
    }
    $this->tasks = json_decode(file_get_contents('tasks.json'), true);
  }
  private function writeTasks()
  {
    $json = json_encode($this->tasks);
    $handle = fopen(self::PATH, 'w');
    fwrite($handle, $json);
    fclose($handle);
  }
  public function add(string $description): int
  {
    if (($c = count($this->tasks)) > 0)
      $id = $this->tasks[$c - 1]['id'] + 1;
    else
      $id = 1;
    $task = ['id' => $id, 'description' => $description, 'status' => 'todo', 'createdAt' => time(), 'updatedAt' => time()];
    $this->tasks[] = $task;
    $this->writeTasks();
    return $id;
  }
  public function update(int $id, string $description)
  {
    if (($task_key = array_find_key($this->tasks, function ($task) use ($id) {
      return $id === $task['id'];
    })) !== null) {
      $this->tasks[$task_key]['description'] = $description;
      $this->tasks[$task_key]['updatedAt'] = time();
      $this->writeTasks();
    }
  }
  public function delete(int $id)
  {
    if (($task_key = array_find_key($this->tasks, function ($task) use ($id) {
      return $id === $task['id'];
    })) !== null) {
      echo 'key is ', $task_key, PHP_EOL;
      unset($this->tasks[$task_key]);
      $this->writeTasks();
    }
  }
  public function markInProgress(int $id)
  {
    if (($task_key = array_find_key($this->tasks, function ($task) use ($id) {
      return $id === $task['id'];
    })) !== null) {
      $this->tasks[$task_key]['status'] = 'in-progress';
      $this->writeTasks();
    }
  }
  public function markDone(int $id)
  {
    if (($task_key = array_find_key($this->tasks, function ($task) use ($id) {
      return $id === $task['id'];
    })) !== null) {
      $this->tasks[$task_key]['status'] = 'done';
      $this->writeTasks();
    }
  }
  public function list(?ListKind $lk = ListKind::All): array
  {
    switch ($lk) {
      case ListKind::All:
        return $this->tasks;
        break;
      case ListKind::Todo:
        return array_filter($this->tasks, function ($task) {
          return $task['status'] === 'todo';
        });
        break;
      case ListKind::InProgress:
        return array_filter($this->tasks, function ($task) {
          return $task['status'] === 'in-progress';
        });
        break;
      case ListKind::Done:
        return array_filter($this->tasks, function ($task) {
          return $task['status'] === 'done';
        });
        break;
    }
    return $this->tasks;
  }
}
