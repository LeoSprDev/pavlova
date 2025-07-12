<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📦 Relance : Confirmation réception livraison requise</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #fd7e14 0%, #e83e8c 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
        .warning-badge { background: #fd7e14; color: white; padding: 8px 16px; border-radius: 20px; font-weight: bold; display: inline-block; margin: 10px 0; }
        .alert-box { background: #fff3cd; padding: 20px; border-radius: 8px; border-left: 4px solid #fd7e14; margin: 20px 0; }
        .urgent-button { background: #dc3545; color: white; padding: 15px 30px; text-decoration: none; border-radius: 6px; display: inline-block; margin: 15px 0; font-weight: bold; }
        .info-box { background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #007bff; margin: 20px 0; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        .retard { font-size: 20px; font-weight: bold; color: #dc3545; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📦 Relance Livraison</h1>
            <p>Action requise : Confirmer réception</p>
        </div>
        
        <div class="content">
            <div class="warning-badge">⚠️ ACTION REQUISE</div>
            
            <p>Bonjour <strong>{{ $demande->createdBy->name }}</strong>,</p>
            
            <div class="alert-box">
                <h3>⏰ Livraison en attente de confirmation</h3>
                <p>Votre livraison était prévue le <strong>{{ $livraison->date_livraison_prevue->format('d/m/Y') }}</strong></p>
                <p class="retard">⚠️ En retard de {{ $jours_retard }} jour(s)</p>
            </div>
            
            <p><strong>Merci de confirmer la réception</strong> de votre commande en uploadant le bon de livraison signé.</p>
            
            <div class="info-box">
                <h3>📦 Détails commande</h3>
                <p><strong>Demande :</strong> {{ $demande->denomination }}</p>
                <p><strong>Référence :</strong> {{ $demande->reference ?? 'N/A' }}</p>
                <p><strong>Quantité :</strong> {{ $demande->quantite }}</p>
                <p><strong>Service :</strong> {{ $demande->serviceDemandeur->nom }}</p>
                <p><strong>Montant :</strong> {{ number_format($demande->prix_total_ttc, 2) }}€</p>
            </div>
            
            <div class="alert-box">
                <h3>📋 Actions à effectuer</h3>
                <p>1. <strong>Vérifier</strong> que vous avez bien reçu le matériel</p>
                <p>2. <strong>Scanner/Photographier</strong> le bon de livraison signé</p>
                <p>3. <strong>Uploader le document</strong> dans l'application</p>
                <p>4. <strong>Marquer conforme</strong> si tout est OK</p>
            </div>
            
            <p><a href="{{ config('app.url') }}/admin/livraisons/{{ $livraison->id }}" class="urgent-button">🚀 CONFIRMER RÉCEPTION</a></p>
            
            <p><strong>Important :</strong> Sans confirmation de votre part, votre budget ne sera pas mis à jour et le processus restera bloqué.</p>
            
            <p><small><em>📧 Relance automatique envoyée le {{ now()->format('d/m/Y à H:i') }}</em></small></p>
            
            @if($jours_retard > 10)
                <div class="alert-box">
                    <p><strong>⚠️ Attention :</strong> Après 15 jours sans confirmation, votre responsable budget sera automatiquement notifié.</p>
                </div>
            @endif
        </div>
        
        <div class="footer">
            <p>Système de Gestion Budgétaire & Workflow d'Achat</p>
            <p>Cette relance est envoyée automatiquement tous les 7 jours</p>
        </div>
    </div>
</body>
</html>
