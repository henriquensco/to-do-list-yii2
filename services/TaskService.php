<?php

namespace app\services;

use app\models\Task;
use Yii;

class TaskService
{
  public Task $taskModel;
  public $userId;

  public function __construct(
    int $userId = null
  )
  {
    $this->taskModel = new Task();
    
    $this->userId = $userId ?? Yii::$app->user->identity->id;

    $this->taskModel->user_id = $this->userId;
  }

  public function list($filters = ['status_id' => null], $orderBy = [])
  {
    $order = [];

    switch ($orderBy) {
      case 'created_at_asc':
        $order = ['created_at' => SORT_ASC];
        break;
      case 'created_at_desc':
        $order = ['created_at' => SORT_DESC];
        break;
      case 'expiration_date_asc':
        $order = ['expiration_date' => SORT_ASC];
        break;
      case 'expiration_date_desc':
        $order = ['expiration_date' => SORT_DESC];
        break;
    }

    $filter = [];
    $filter['user_id'] = $this->userId;
   
    if ($filters['status_id'] == null) {
      unset($filters['status_id']);
    } else {
      $filter['status_id'] = $filters['status_id'];
    }

    if ($filters['priority'] == null) {
      unset($filters['priority']);
    } else {
      $filter['priority'] = $filters['priority'];
    }

    return $this->taskModel
      ->find()
      ->where($filter)
      ->addOrderBy($order)
      ->all();
  }

  public function get($id)
  {
    return $this->taskModel->find()->where([
      'id' => $id,
      'user_id' => $this->userId
    ])->one();
  }

  public function create($data)
  {
    if (!$this->validateExpirationDate($data->expiration_date)) {
      return ['success' => false, 'data' => 'Expiration date must be greater than today'];
    }

    $this->taskModel->title = $data->title;
    $this->taskModel->priority = $data->priority;
    $this->taskModel->status_id = $data->status_id;
    $this->taskModel->description = $data->description;
    $this->taskModel->expiration_date = $data->expiration_date;

    if (!$this->taskModel->save()) {
      return ['success' => false, 'data' => $this->taskModel->getErrors()];
    }

    return ['success' => true, 'data' => $this->taskModel];
  }

  public function update($data, $id)
  {
    $task = $this->get($id);

    if (!$task) {
      return ['success' => false, 'data' => 'Task not found'];
    }

    $task->id = $id;
    $task->title = $data->title;
    $task->priority = $data->priority;
    $task->status_id = $data->status_id;
    $task->description = $data->description;
    $task->expiration_date = $data->expiration_date;
    $task->user_id = $this->userId;

    if (!$task->save()) {
      return ['success' => false, 'data' => $task->getErrors()];
    }

    return ['success' => true, 'message' => $task];
  }

  public function delete($id)
  {
    $task = $this->get($id);

    if (!$task) {
      return ['success' => false, 'data' => 'Task not found'];
    }

    $task->delete();

    return ['success' => true, 'data' => 'Task deleted'];
  }

  private function validateExpirationDate($expirationDate)
  {
    if ($expirationDate < date('Y-m-d')) {
      return false;
    }

    return true;
  }
}
