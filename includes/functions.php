<?php
// includes/functions.php

function sanitize(string $v): string {
    return htmlspecialchars(strip_tags(trim($v)), ENT_QUOTES, 'UTF-8');
}

function getRiskLabel(string $r): array {
    return match($r) {
        'low'    => ['label'=>'Baixo Risco',  'class'=>'risk-low',    'icon'=>'🟢','color'=>'#43A047'],
        'medium' => ['label'=>'Médio Risco',  'class'=>'risk-medium', 'icon'=>'🟡','color'=>'#FDD835'],
        'high'   => ['label'=>'Alto Risco',   'class'=>'risk-high',   'icon'=>'🔴','color'=>'#E53935'],
        default  => ['label'=>'Indefinido',   'class'=>'risk-medium', 'icon'=>'⚪','color'=>'#999'],
    };
}

function getPreferenceLabel(string $p): string {
    return match($p) {
        'credit'     => 'Crédito',
        'investment' => 'Investimento',
        'savings'    => 'Poupança',
        'loan'       => 'Empréstimo',
        'card'       => 'Cartão',
        default      => ucfirst($p),
    };
}

function getPreferenceIcon(string $p): string {
    return match($p) {
        'credit'     => '💳',
        'investment' => '📈',
        'savings'    => '🏦',
        'loan'       => '💰',
        'card'       => '🪙',
        default      => '💼',
    };
}

function getChallengeLabel(string $c): string {
    return match($c) {
        'debt'         => 'Dívidas',
        'lack_control' => 'Sem controle financeiro',
        'low_income'   => 'Renda baixa',
        'no_credit'    => 'Sem acesso a crédito',
        'fraud_risk'   => 'Risco de fraude',
        'illiteracy'   => 'Analfabetismo financeiro',
        default        => ucfirst($c),
    };
}

function calculateRisk(array $challenges): string {
    $hi  = ['debt','fraud_risk','no_credit'];
    $med = ['lack_control','low_income'];
    $score = 0;
    foreach ($challenges as $c) {
        if (in_array($c,$hi))  $score += 3;
        elseif (in_array($c,$med)) $score += 2;
        else $score += 1;
    }
    if ($score >= 6) return 'high';
    if ($score >= 3) return 'medium';
    return 'low';
}

function formatDate(string $d): string {
    return (new DateTime($d))->format('d/m/Y H:i');
}

function getInitials(string $name): string {
    $words = explode(' ', trim($name));
    return mb_strtoupper(
        mb_substr($words[0],0,1) . (isset($words[1]) ? mb_substr($words[1],0,1) : '')
    );
}

function jsonResponse(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
