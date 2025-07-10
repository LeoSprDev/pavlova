<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>✅ Livraison conforme validée</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
        .success-badge { background: #28a745; color: white; padding: 8px 16px; border-radius: 20px; font-weight: bold; display: inline-block; margin: 10px 0; }
        .info-box { background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #007bff; margin: 20px 0; }
        .button { background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block; margin: 15px 0; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        .amount { font-size: 24px; font-weight: bold; color: #28a745; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✅ Livraison Conforme Validée</h1>
            <p>Votre demande a été finalisée avec succès</p>
        </div>
        
        <div class="content">
            <div class="success-badge">✅ LIVRAISON CONFIRMÉE</div>
            
            <p>Bonjour <strong>{{ $demande->createdBy->name }}</strong>,</p>
            
            <p>Votre livraison a été marquée comme <strong>conforme</strong> et le processus est maintenant terminé.</p>
            
            <div class="info-box">
                <h3>📦 Détails de la livraison</h3>
                <p><strong>Demande :</strong> {{ $demande->denomination }}</p>
                <p><strong>Référence :</strong> {{ $demande->reference ?? 'N/A' }}</p>
                <p><strong>Quantité :</strong> {{ $demande->quantite }}</p>
                <p><strong>Service :</strong> {{ $demande->serviceDemandeur->nom }}</p>
                <p><strong>Date livraison :</strong> {{ $livraison->date_livraison_reelle?->format('d/m/Y') ?? 'N/A' }}</p>
            </div>
            
            <div class="info-box">
                <h3>💰 Impact budgétaire</h3>
                <p><strong>Montant budgété :</strong> {{ number_format($demande->prix_total_ttc, 2) }}€</p>
                @if($montant_reel)
                    <p><strong>Montant réel fournisseur :</strong> <span class="amount">{{ number_format($montant_reel, 2) }}€</span></p>
                    @if($montant_reel != $demande->prix_total_ttc)
                        <p><em>💡 Différence : {{ number_format($montant_reel - $demande->prix_total_ttc, 2) }}€</em></p>
                    @endif
                @endif
                <p><small>✅ Votre budget ligne a été automatiquement mis à jour</small></p>
            </div>
            
            <p><a href="{{ config('app.url') }}/admin/mes-demandes" class="button">📊 Voir mes demandes</a></p>
            
            <p>Merci d'avoir utilisé notre système de gestion budgétaire.</p>
            
            <p><small><em>📧 Email automatique envoyé le {{ now()->format('d/m/Y à H:i') }}</em></small></p>
        </div>
        
        <div class="footer">
            <p>Système de Gestion Budgétaire & Workflow d'Achat</p>
            <p>Pour toute question, contactez votre responsable budget</p>
        </div>
    </div>
</body>
</html>
