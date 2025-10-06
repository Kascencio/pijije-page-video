<?php
require_once __DIR__ . '/../lib/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
if(!isLoggedIn()) { http_response_code(401); echo json_encode(['error'=>'auth']); exit; }
try {
  $db = getDB();
  $userId = getCurrentUserId();
  $rows = $db->fetchAll('SELECT video_id, seconds, duration, completed_at FROM user_video_progress WHERE user_id=?', [$userId]);
  echo json_encode(['ok'=>true,'items'=>$rows]);
} catch(Throwable $e){
  error_log('[GET PROGRESS] '.$e->getMessage());
  http_response_code(500); echo json_encode(['error'=>'internal']);
}
