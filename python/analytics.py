#!/usr/bin/env python3
"""
python/analytics.py
FinSight – Motor de Análise Avançada
Lê do banco SQLite e retorna JSON estatístico.

Uso:
  python3 analytics.py                    → relatório completo
  python3 analytics.py --report=summary   → KPIs
  python3 analytics.py --report=risk      → análise de risco
  python3 analytics.py --report=trends    → tendências
  python3 analytics.py --report=geo       → dados geográficos

Pode ser chamado pelo PHP via shell_exec() ou proc_open().
"""

import sqlite3
import json
import sys
import os
from collections import Counter
from datetime import datetime

DB_PATH = os.path.join(os.path.dirname(__file__), '..', 'database', 'finsight.db')

LABELS = {
    'preference': {
        'credit': 'Crédito', 'investment': 'Investimento',
        'savings': 'Poupança', 'loan': 'Empréstimo', 'card': 'Cartão',
    },
    'challenge': {
        'debt': 'Dívidas', 'lack_control': 'Falta de controle',
        'low_income': 'Renda baixa', 'no_credit': 'Sem crédito',
        'fraud_risk': 'Risco de fraude', 'illiteracy': 'Analfabetismo financeiro',
    },
    'risk': {
        'low': 'Baixo Risco', 'medium': 'Médio Risco', 'high': 'Alto Risco',
    }
}


def conn():
    c = sqlite3.connect(DB_PATH)
    c.row_factory = sqlite3.Row
    return c


def summary(db):
    total   = db.execute("SELECT COUNT(*) FROM responses").fetchone()[0]
    agents  = db.execute("SELECT COUNT(DISTINCT agent_id) FROM responses").fetchone()[0]
    today   = db.execute("SELECT COUNT(*) FROM responses WHERE DATE(created_at)=DATE('now')").fetchone()[0]
    week    = db.execute("SELECT COUNT(*) FROM responses WHERE created_at>=DATE('now','-7 days')").fetchone()[0]

    risk_rows = db.execute("SELECT risk_level,COUNT(*) c FROM responses GROUP BY risk_level").fetchall()
    risk_dist = {r['risk_level']: r['c'] for r in risk_rows}
    high, mid, low = risk_dist.get('high',0), risk_dist.get('medium',0), risk_dist.get('low',0)

    pref_rows = db.execute("SELECT financial_preference,COUNT(*) c FROM responses GROUP BY financial_preference ORDER BY c DESC").fetchall()
    pref_dist = [{'key': r['financial_preference'], 'label': LABELS['preference'].get(r['financial_preference'], r['financial_preference']), 'count': r['c']} for r in pref_rows]
    top_pref  = pref_dist[0] if pref_dist else None

    chal_rows = db.execute("SELECT challenge,COUNT(*) c FROM response_challenges GROUP BY challenge ORDER BY c DESC").fetchall()
    chal_dist = [{'key': r['challenge'], 'label': LABELS['challenge'].get(r['challenge'], r['challenge']), 'count': r['c']} for r in chal_rows]
    top_chal  = chal_dist[0] if chal_dist else None

    return {
        'total_responses': total,
        'active_agents': agents,
        'today_count': today,
        'week_count': week,
        'risk': {
            'low': low, 'medium': mid, 'high': high,
            'high_pct': round(high/total*100, 1) if total else 0,
        },
        'top_preference': top_pref,
        'top_challenge': top_chal,
        'preference_distribution': pref_dist,
        'challenge_distribution': chal_dist,
    }


def trends(db):
    monthly = db.execute("""
        SELECT strftime('%Y-%m', created_at) ym,
               strftime('%m/%Y', created_at) lbl,
               COUNT(*) total,
               SUM(CASE WHEN risk_level='high'   THEN 1 ELSE 0 END) high,
               SUM(CASE WHEN risk_level='medium' THEN 1 ELSE 0 END) medium,
               SUM(CASE WHEN risk_level='low'    THEN 1 ELSE 0 END) low
        FROM responses
        WHERE created_at >= DATE('now','-12 months')
        GROUP BY ym ORDER BY ym
    """).fetchall()

    weekly = db.execute("""
        SELECT strftime('%Y-%W', created_at) yw,
               COUNT(*) total
        FROM responses
        WHERE created_at >= DATE('now','-8 weeks')
        GROUP BY yw ORDER BY yw
    """).fetchall()

    monthly_data = [dict(r) for r in monthly]
    growth = 0
    if len(monthly_data) >= 2:
        c, p = monthly_data[-1]['total'], monthly_data[-2]['total']
        if p: growth = round((c - p) / p * 100, 1)

    return {
        'monthly': monthly_data,
        'weekly': [dict(r) for r in weekly],
        'growth_rate_pct': growth,
    }


def risk_analysis(db):
    # Risco por preferência
    rows = db.execute("""
        SELECT financial_preference, risk_level, COUNT(*) c
        FROM responses GROUP BY financial_preference, risk_level
    """).fetchall()
    by_pref = {}
    for r in rows:
        p = r['financial_preference']
        if p not in by_pref:
            by_pref[p] = {'label': LABELS['preference'].get(p, p), 'low': 0, 'medium': 0, 'high': 0}
        by_pref[p][r['risk_level']] = r['c']

    # Correlação desafio × alto risco
    crows = db.execute("""
        SELECT rc.challenge,
               COUNT(*) total,
               SUM(CASE WHEN r.risk_level='high' THEN 1 ELSE 0 END) high_count
        FROM response_challenges rc
        JOIN responses r ON r.id=rc.response_id
        GROUP BY rc.challenge ORDER BY high_count DESC
    """).fetchall()
    chal_risk = [{
        'challenge': r['challenge'],
        'label': LABELS['challenge'].get(r['challenge'], r['challenge']),
        'total': r['total'],
        'high_count': r['high_count'],
        'high_pct': round(r['high_count'] / r['total'] * 100, 1) if r['total'] else 0,
    } for r in crows]

    # Combinações de desafios mais perigosas
    combos_rows = db.execute("""
        SELECT r.id, GROUP_CONCAT(rc.challenge,'+') combo
        FROM responses r JOIN response_challenges rc ON rc.response_id=r.id
        WHERE r.risk_level='high'
        GROUP BY r.id
    """).fetchall()
    combos = Counter()
    for row in combos_rows:
        if row['combo']:
            key = '+'.join(sorted(row['combo'].split('+')))
            combos[key] += 1
    top_combos = [{'combo': k, 'count': v} for k, v in combos.most_common(5)]

    return {
        'risk_by_preference': by_pref,
        'challenge_risk_correlation': chal_risk,
        'top_risk_combinations': top_combos,
    }


def geo(db):
    rows = db.execute("""
        SELECT interviewee_name name, financial_preference pref,
               risk_level risk, latitude lat, longitude lng
        FROM responses
        WHERE latitude IS NOT NULL AND longitude IS NOT NULL
    """).fetchall()
    points = [dict(r) for r in rows]

    # Bounding box
    if points:
        lats = [p['lat'] for p in points]
        lngs = [p['lng'] for p in points]
        bbox = {'min_lat': min(lats), 'max_lat': max(lats),
                'min_lng': min(lngs), 'max_lng': max(lngs)}
    else:
        bbox = None

    return {
        'total_with_location': len(points),
        'points': points,
        'bounding_box': bbox,
    }


def run():
    report = 'full'
    for arg in sys.argv[1:]:
        if arg.startswith('--report='):
            report = arg.split('=', 1)[1]

    if not os.path.exists(DB_PATH):
        print(json.dumps({'error': f'Banco de dados não encontrado: {DB_PATH}'}))
        sys.exit(1)

    db = conn()
    try:
        if report == 'summary':
            result = summary(db)
        elif report == 'trends':
            result = trends(db)
        elif report == 'risk':
            result = risk_analysis(db)
        elif report == 'geo':
            result = geo(db)
        else:  # full
            result = {
                'summary': summary(db),
                'trends':  trends(db),
                'risk':    risk_analysis(db),
                'geo':     geo(db),
                'generated_at': datetime.utcnow().isoformat() + 'Z',
                'version': '1.0.0',
            }
        print(json.dumps(result, ensure_ascii=False, indent=2))
    finally:
        db.close()


if __name__ == '__main__':
    run()
