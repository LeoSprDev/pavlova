<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport Exécutif Budget</title>
    <style>
        body{font-family: 'Inter', sans-serif;font-size:12px;line-height:1.6;color:#1f2937;background:#fff;}
        .header{background:#1f2937;color:#fff;padding:30px;text-align:center;margin-bottom:30px;}
        .kpi-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-bottom:40px;}
        .kpi-card{background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:20px;text-align:center;}
        .section{margin-bottom:40px;padding:25px;border:1px solid #e5e7eb;border-radius:12px;}
        .section-title{font-size:18px;font-weight:600;margin-bottom:20px;padding-bottom:10px;border-bottom:2px solid #3b82f6;}
        .performance-table{width:100%;border-collapse:collapse;margin-top:15px;}
        .performance-table th,.performance-table td{padding:12px;text-align:left;border-bottom:1px solid #e5e7eb;}
        .performance-table th{background:#f9fafb;font-weight:600;color:#374151;}
        .status-badge{display:inline-block;padding:4px 8px;border-radius:6px;font-size:10px;font-weight:500;text-transform:uppercase;}
        .status-success{background:#d1fae5;color:#065f46;}
        .status-warning{background:#fef3c7;color:#92400e;}
        .status-danger{background:#fee2e2;color:#991b1b;}
        .recommendations{background:#f0f9ff;border-left:4px solid #3b82f6;padding:20px;margin:20px 0;}
        .footer{margin-top:50px;padding-top:20px;border-top:1px solid #e5e7eb;text-align:center;font-size:10px;color:#6b7280;}
        .page-break{page-break-before:always;}
    </style>
</head>
<body>
<div class="header">
    <h1>📊 RAPPORT EXÉCUTIF BUDGET</h1>
    <div class="subtitle">Période: {{ $data['periode_affichage'] ?? 'Année '.now()->year }}<br>Généré le {{ $generated_at->format('d/m/Y H:i') }} par {{ $user->name }}</div>
</div>
<div class="kpi-grid">
    @foreach($data['kpis_principaux'] as $kpi)
    <div class="kpi-card">
        <div class="kpi-value">{{ $kpi['value'] }}</div>
        <div class="kpi-label">{{ $kpi['label'] }}</div>
    </div>
    @endforeach
</div>
<div class="section">
    <h2 class="section-title">🏢 Performance par Service</h2>
    <table class="performance-table">
        <thead>
        <tr><th>Service</th><th>Budget Alloué</th><th>Consommé</th><th>Disponible</th><th>Taux</th><th>Statut</th></tr>
        </thead>
        <tbody>
        @foreach($data['performance_services'] as $service)
            <tr>
                <td>{{ $service['nom'] }}</td>
                <td>{{ number_format($service['budget_alloue'],2) }} €</td>
                <td>{{ number_format($service['budget_consomme'],2) }} €</td>
                <td>{{ number_format($service['budget_disponible'],2) }} €</td>
                <td>{{ round($service['taux_utilisation'],1) }}%</td>
                <td><span class="status-badge status-{{ $service['status_class'] }}">{{ $service['status_text'] }}</span></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
<div class="recommendations">
    <h4>Recommandations</h4>
    <ul>
        @foreach($data['recommandations'] as $rec)
            <li>{{ $rec }}</li>
        @endforeach
    </ul>
</div>
<div class="footer">Document généré automatiquement - Pavlova</div>
</body>
</html>
