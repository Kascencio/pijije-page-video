<?php
require_once __DIR__ . '/../lib/bootstrap.php';
require_once __DIR__ . '/../lib/access.php';
header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) { http_response_code(401); echo json_encode(['error'=>'auth']); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error'=>'method']); exit; }

$in = json_decode(file_get_contents('php://input'), true) ?: [];
$videoId = isset($in['video_id']) ? (int)$in['video_id'] : 0;
$seconds = isset($in['seconds']) ? (int)$in['seconds'] : 0;
$duration = isset($in['duration']) ? (int)$in['duration'] : null;
if ($videoId<=0) { http_response_code(422); echo json_encode(['error'=>'video_id']); exit; }
if ($seconds<0) $seconds=0;

try {
  $db = getDB();
  // Verificar que el video pertenece al curso del usuario (básico)
  $video = $db->fetchOne('SELECT id FROM videos WHERE id = ?', [$videoId]);
  if (!$video) { http_response_code(404); echo json_encode(['error'=>'not_found']); exit; }

  $fields = [
    'user_id' => getCurrentUserId(),
    'video_id' => $videoId,
    'seconds' => $seconds,
    'updated_at' => date('Y-m-d H:i:s')
  ];
  if ($duration && $duration>0) { $fields['duration'] = $duration; }
  if ($duration && $seconds>0 && $duration>0 && $seconds >= ($duration-5)) {
    $fields['completed_at'] = date('Y-m-d H:i:s');
  }

  // UPSERT manual
  $exists = $db->fetchOne('SELECT id FROM user_video_progress WHERE user_id=? AND video_id=?', [getCurrentUserId(), $videoId]);
  if ($exists) {
    $db->update('user_video_progress', $fields, 'id=?', [$exists['id']]);
  } else {
    $db->insert('user_video_progress', $fields);
  }

  // Calcular progreso global rápido
  $stats = $db->fetchOne('SELECT COUNT(*) completed FROM user_video_progress WHERE user_id=? AND completed_at IS NOT NULL', [getCurrentUserId()]);
  echo json_encode(['ok'=>true,'completed'=>$stats['completed'] ?? 0]);
} catch (Throwable $e) {
  error_log('[PROGRESS SAVE] '.$e->getMessage());
  http_response_code(500); echo json_encode(['error'=>'internal']);
}
