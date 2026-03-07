@php use Illuminate\Support\Str; @endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Facture {{ $commande['numero_commande'] }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #4CAF50;
        }
        .company-info h1 {
            color: #4CAF50;
            margin: 0 0 10px 0;
            font-size: 24px;
        }
        .company-info p {
            margin: 3px 0;
            color: #666;
        }
        .invoice-info {
            text-align: right;
        }
        .invoice-info h2 {
            color: #4CAF50;
            margin: 0 0 10px 0;
            font-size: 20px;
        }
        .invoice-info .badge {
            display: inline-block;
            padding: 5px 15px;
            background: #4CAF50;
            color: white;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .client-section {
            margin-bottom: 30px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 8px;
        }
        .client-section h3 {
            margin: 0 0 15px 0;
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        .client-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .client-info p {
            margin: 5px 0;
        }
        .client-info strong {
            color: #555;
        }
        .dates-section {
            margin-bottom: 30px;
        }
        .dates-grid {
            display: flex;
            gap: 20px;
        }
        .date-box {
            flex: 1;
            padding: 10px 15px;
            background: #f5f5f5;
            border-radius: 6px;
            border-left: 4px solid #4CAF50;
        }
        .date-box strong {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-size: 11px;
            text-transform: uppercase;
        }
        .date-box span {
            font-size: 14px;
            font-weight: bold;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        th {
            background: #4CAF50;
            color: white;
            padding: 12px;
            text-align: left;
            font-size: 12px;
        }
        td {
            padding: 10px 12px;
            border-bottom: 1px solid #eee;
        }
        .article-name {
            font-weight: bold;
            color: #333;
        }
        .article-desc {
            font-size: 11px;
            color: #666;
            margin-top: 3px;
        }
        .text-right {
            text-align: right;
        }
        .totals {
            width: 350px;
            margin-left: auto;
            margin-top: 20px;
        }
        .totals table {
            width: 100%;
            margin-bottom: 0;
        }
        .totals td {
            padding: 8px 12px;
            border: none;
        }
        .totals .grand-total {
            font-weight: bold;
            font-size: 16px;
            background: #f5f5f5;
            border-top: 2px solid #4CAF50;
        }
        .totals .grand-total td {
            padding: 12px;
        }
        .discount {
            color: #e53935;
        }
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #999;
            font-size: 10px;
        }
        .payment-info {
            margin-top: 30px;
            padding: 15px;
            background: #f5f5f5;
            border-radius: 8px;
            font-size: 11px;
        }
        .payment-info p {
            margin: 5px 0;
        }
        .note {
            margin-top: 20px;
            padding: 10px;
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            font-size: 11px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-info">
            <h1>{{ $entreprise['nom'] }}</h1>
            <p>{{ $entreprise['adresse'] }}</p>
            <p>SIRET: {{ $entreprise['siret'] }} | TVA: {{ $entreprise['tva_intra'] }}</p>
            <p>Tél: {{ $entreprise['telephone'] }} | Email: {{ $entreprise['email'] }}</p>
        </div>
        <div class="invoice-info">
            <h2>FACTURE</h2>
            <p><strong>N° {{ $commande['numero_commande'] }}</strong></p>
            <p>Date: {{ $commande['date_commande'] }}</p>
            <div class="badge">{{ $commande['statut_libelle'] }}</div>
        </div>
    </div>

    <div class="client-section">
        <h3>Client</h3>
        <div class="client-info">
            <div>
                <p><strong>Nom / Société</strong><br>{{ $commande['client']['nom_complet'] }}</p>
                @if($commande['client']['type'] === 'professionnel')
                    <p><strong>Société</strong><br>{{ $commande['client']['nom_societe'] }}</p>
                @endif
                <p><strong>Email</strong><br>{{ $commande['client']['email'] }}</p>
            </div>
            <div>
                @if(!empty($commande['client']['telephone']))
                    <p><strong>Téléphone</strong><br>{{ $commande['client']['telephone'] }}</p>
                @endif
                @if(isset($commande['client']['contact_nom']))
                    <p><strong>Contact</strong><br>{{ $commande['client']['contact_nom'] }}</p>
                @endif
                @if(isset($commande['client']['contact_email']))
                    <p><strong>Email contact</strong><br>{{ $commande['client']['contact_email'] }}</p>
                @endif
            </div>
        </div>
    </div>

    <div class="dates-section">
        <div class="dates-grid">
            <div class="date-box">
                <strong>Date de début</strong>
                <span>{{ $commande['date_debut'] }}</span>
            </div>
            <div class="date-box">
                <strong>Date de fin</strong>
                <span>{{ $commande['date_fin'] }}</span>
            </div>
            <div class="date-box">
                <strong>Durée</strong>
                <span>{{ $commande['duree'] }}</span>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Article</th>
                <th class="text-right">Quantité</th>
                <th class="text-right">Prix HT</th>
                <th class="text-right">TVA</th>
                <th class="text-right">Total HT</th>
                <th class="text-right">Total TTC</th>
            </tr>
        </thead>
        <tbody>
            @foreach($commande['articles'] as $article)
            <tr>
                <td>
                    <div class="article-name">{{ $article['nom'] }}</div>
                    @if(!empty($article['description']))
                        <div class="article-desc">{{ Str::limit($article['description'], 50) }}</div>
                    @endif
                </td>
                <td class="text-right">{{ $article['quantite'] }}</td>
                <td class="text-right">{{ number_format($article['prix_unitaire_ht'], 2, ',', ' ') }} €</td>
                <td class="text-right">{{ $article['taux_tva'] }}%</td>
                <td class="text-right">{{ number_format($article['sous_total_ht'], 2, ',', ' ') }} €</td>
                <td class="text-right">{{ number_format($article['sous_total_ttc'], 2, ',', ' ') }} €</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <table>
            <tr>
                <td>Sous-total HT</td>
                <td class="text-right">{{ number_format($commande['totaux']['sous_total_ht'], 2, ',', ' ') }} €</td>
            </tr>
            <tr>
                <td>TVA totale</td>
                <td class="text-right">{{ number_format($commande['totaux']['total_tva'], 2, ',', ' ') }} €</td>
            </tr>
            <tr>
                <td>Sous-total TTC</td>
                <td class="text-right">{{ number_format($commande['totaux']['sous_total_ttc'], 2, ',', ' ') }} €</td>
            </tr>
            @if($commande['totaux']['frais_livraison'] > 0)
            <tr>
                <td>Frais de livraison</td>
                <td class="text-right">{{ number_format($commande['totaux']['frais_livraison'], 2, ',', ' ') }} €</td>
            </tr>
            @endif
            @if($commande['totaux']['frais_retour'] > 0)
            <tr>
                <td>Frais de retour</td>
                <td class="text-right">{{ number_format($commande['totaux']['frais_retour'], 2, ',', ' ') }} €</td>
            </tr>
            @endif
            @if($commande['totaux']['remise'] > 0)
            <tr class="discount">
                <td>Remise</td>
                <td class="text-right">-{{ number_format($commande['totaux']['remise'], 2, ',', ' ') }} €</td>
            </tr>
            @endif
            <tr class="grand-total">
                <td><strong>TOTAL TTC</strong></td>
                <td class="text-right"><strong>{{ number_format($commande['totaux']['total_ttc'], 2, ',', ' ') }} €</strong></td>
            </tr>
        </table>
    </div>

    @if($commande['code_reduction'])
    <div class="payment-info">
        <p><strong>Code promotionnel appliqué :</strong> {{ $commande['code_reduction']['code'] }} 
        ({{ $commande['code_reduction']['montant'] }}{{ $commande['code_reduction']['type_libelle'] }})</p>
    </div>
    @endif

    @if($commande['notes'])
    <div class="note">
        <strong>Note :</strong> {{ $commande['notes'] }}
    </div>
    @endif

    <div class="footer">
        <p>Merci de votre confiance !</p>
        <p>{{ $entreprise['nom'] }} - {{ $entreprise['adresse'] }} - {{ $entreprise['telephone'] }}</p>
        <p>Généré le {{ $date_generation }}</p>
    </div>
</body>
</html>