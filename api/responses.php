<?php
// api/responses.php – REST API
session_start();
require_once '../includes/db.php';
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if(!isLoggedIn()) jsonResponse(['error'=>'Não autorizado'], 401);

$method = $_SERVER['REQUEST_METHOD'];
$u      = currentUser();
$db     = getDB();

// ── GET: listar/buscar ─────────────────────────────────────
if($method==='GET'){
    $id = (int)($_GET['id'] ?? 0);
    if($id){
        $s=$db->prepare('SELECT r.*,GROUP_CONCAT(rc.challenge,",") chals FROM responses r LEFT JOIN response_challenges rc ON rc.response_id=r.id WHERE r.id=? GROUP BY r.id');
        $s->execute([$id]); $row=$s->fetch();
        if(!$row) jsonResponse(['error'=>'Não encontrado'],404);
        $row['challenges']=$row['chals']?explode(',',$row['chals']):[];
        unset($row['chals']);
        jsonResponse(['success'=>true,'data'=>$row]);
    }
    jsonResponse(['success'=>true,'message'=>'API FinSight v1.0']);
}

// ── DELETE: excluir ────────────────────────────────────────
if($method==='DELETE'){
    if($u['role']!=='admin') jsonResponse(['error'=>'Acesso negado'], 403);

    $body = json_decode(file_get_contents('php://input'), true) ?? [];

    // Excluir tudo
    if(!empty($body['all'])){
        $db->exec('DELETE FROM response_challenges');
        $db->exec('DELETE FROM responses');
        jsonResponse(['success'=>true,'message'=>'Todos os dados foram excluídos.']);
    }

    // Excluir por ID
    if(!empty($body['id'])){
        $id=(int)$body['id'];
        $db->prepare('DELETE FROM responses WHERE id=?')->execute([$id]);
        jsonResponse(['success'=>true,'message'=>"Pesquisa #$id excluída."]);
    }

    jsonResponse(['error'=>'Requisição inválida'], 400);
}

// ── POST: criar (via API) ──────────────────────────────────
if($method==='POST'){
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $name  = sanitize($body['name']  ?? '');
    $phone = sanitize($body['phone'] ?? '');
    $pref  = sanitize($body['preference'] ?? '');
    $chals = $body['challenges'] ?? [];
    $lat   = isset($body['latitude'])  ? (float)$body['latitude']  : null;
    $lng   = isset($body['longitude']) ? (float)$body['longitude'] : null;

    $okP=['credit','investment','savings','loan','card'];
    $okC=['debt','lack_control','low_income','no_credit','fraud_risk','illiteracy'];
    $chals=array_values(array_filter($chals,fn($c)=>in_array($c,$okC)));

    if(!$name||!in_array($pref,$okP)||empty($chals))
        jsonResponse(['error'=>'Dados inválidos'],422);

    $risk=$u['role'];
    require_once '../includes/functions.php';
    $risk = calculateRisk($chals);

    $db->beginTransaction();
    try {
        $s=$db->prepare('INSERT INTO responses(agent_id,interviewee_name,interviewee_phone,financial_preference,risk_level,latitude,longitude)VALUES(?,?,?,?,?,?,?)');
        $s->execute([$u['id'],$name,$phone,$pref,$risk,$lat,$lng]);
        $rid=$db->lastInsertId();
        $cs=$db->prepare('INSERT INTO response_challenges(response_id,challenge)VALUES(?,?)');
        foreach($chals as $c) $cs->execute([$rid,$c]);
        $db->commit();
        jsonResponse(['success'=>true,'id'=>$rid,'risk_level'=>$risk]);
    } catch(Exception $e){
        $db->rollBack();
        jsonResponse(['error'=>'Erro ao criar pesquisa'],500);
    }
}

jsonResponse(['error'=>'Método não permitido'],405);
