<?php
require_once 'db.php';

// --- TRAITEMENT DES FORMULAIRES (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ajout vehicule
    if ($action === 'ajouter_vehicule') {
        $immat = trim($_POST['immatriculation'] ?? '');
        $marque = trim($_POST['marque'] ?? '');
        $modele = trim($_POST['modele'] ?? '');
        $km = (int)($_POST['kilometrage'] ?? 0);
        $vidange = (int)($_POST['km_derniere_vidange'] ?? 0);

        if (!empty($immat) && !empty($marque) && $km > 0) {
            $stmt = $pdo->prepare("INSERT INTO vehicules (immatriculation, marque, modele, kilometrage, km_derniere_vidange) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$immat, $marque, $modele, $km, $vidange]);
            $vehicule_id = (int)$pdo->lastInsertId();
            header("Location: index.php?vehicule_id=" . $vehicule_id);
            exit;
        }
    }

    // ajout plein
    if ($action === 'ajouter_plein') {
        $v_id = (int)($_POST['vehicule_id'] ?? 0);
        $date = $_POST['date_plein'] ?? date('Y-m-d');
        $km = (int)($_POST['kilometrage'] ?? 0);
        $litres = (float)($_POST['litres'] ?? 0);
        $prix = (float)($_POST['prix_total'] ?? 0);

        if ($v_id > 0 && $km > 0 && $litres > 0 && $prix > 0) {
            $prix_litre = round($prix / $litres, 3);
            $stmt = $pdo->prepare("INSERT INTO pleins (vehicule_id, date_plein, kilometrage, litres, prix_total, prix_litre) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$v_id, $date, $km, $litres, $prix, $prix_litre]);

            $pdo->prepare("UPDATE vehicules SET kilometrage = GREATEST(kilometrage, ?) WHERE id = ?")->execute([$km, $v_id]);
            header("Location: index.php?vehicule_id=" . $v_id);
            exit;
        }
    }

    // ajout piece
    if ($action === 'ajouter_piece') {
        $v_id = (int)($_POST['vehicule_id'] ?? 0);
        $nom = trim($_POST['nom'] ?? '');
        $ref = trim($_POST['reference'] ?? '');
        $prix = (float)($_POST['prix'] ?? 0);
        $statut = $_POST['statut'] ?? 'commandee';
        $date = $_POST['date_operation'] ?? date('Y-m-d');

        if ($v_id > 0 && !empty($nom) && $prix >= 0) {
            $stmt = $pdo->prepare("INSERT INTO pieces (vehicule_id, nom, reference, prix, statut, date_operation) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$v_id, $nom, $ref, $prix, $statut, $date]);
            header("Location: index.php?vehicule_id=" . $v_id);
            exit;
        }
    }

    // suppression d'un plein
    if ($action === 'supprimer_plein') {
        $id = (int)($_POST['id'] ?? 0);
        $v_id = (int)($_POST['vehicule_id'] ?? 0);
        $pdo->prepare("DELETE FROM pleins WHERE id = ?")->execute([$id]);
        header("Location: index.php?vehicule_id=" . $v_id);
        exit;
    }

    // suppression d'une piece
    if ($action === 'supprimer_piece') {
        $id = (int)($_POST['id'] ?? 0);
        $v_id = (int)($_POST['vehicule_id'] ?? 0);
        $pdo->prepare("DELETE FROM pieces WHERE id = ?")->execute([$id]);
        header("Location: index.php?vehicule_id=" . $v_id);
        exit;
    }
}

// --- DONNEES DU VEHICULE SELECTIONNE ---
$vehicules = $pdo->query("SELECT * FROM vehicules ORDER BY id DESC")->fetchAll();
$current_id = isset($_GET['vehicule_id']) ? (int)$_GET['vehicule_id'] : ($vehicules[0]['id'] ?? 0);

$current_vehicule = null;
foreach ($vehicules as $v) {
    if ($v['id'] === $current_id) {
        $current_vehicule = $v;
        break;
    }
}

// --- HISTORIQUE & CALCUL CONSO ---
$pleins = [];
$total_depense_carburant = 0;
$total_litres = 0;
$conso_moyenne = 0;

if ($current_vehicule) {
    $stmt = $pdo->prepare("SELECT * FROM pleins WHERE vehicule_id = ? ORDER BY kilometrage ASC");
    $stmt->execute([$current_id]);
    $pleins_asc = $stmt->fetchAll();

    $nb_pleins = count($pleins_asc);
    if ($nb_pleins >= 2) {
        $km_parcourus = $pleins_asc[$nb_pleins - 1]['kilometrage'] - $pleins_asc[0]['kilometrage'];
        $litres_intermediaires = 0;
        for ($i = 1; $i < $nb_pleins; $i++) {
            $litres_intermediaires += $pleins_asc[$i]['litres'];
        }
        if ($km_parcourus > 0) {
            $conso_moyenne = round(($litres_intermediaires / $km_parcourus) * 100, 2);
        }
    }

    foreach ($pleins_asc as $p) {
        $total_depense_carburant += $p['prix_total'];
        $total_litres += $p['litres'];
    }

    $pleins = array_reverse($pleins_asc);

    $stmt = $pdo->prepare("SELECT * FROM pieces WHERE vehicule_id = ? ORDER BY date_operation DESC");
    $stmt->execute([$current_id]);
    $pieces = $stmt->fetchAll();

    $total_pieces = array_sum(array_column($pieces, 'prix'));
}

$km_depuis_vidange = $current_vehicule ? ($current_vehicule['kilometrage'] - $current_vehicule['km_derniere_vidange']) : 0;
$intervalle = $current_vehicule['intervalle_vidange'] ?? 15000;
$pourcent_vidange = min(100, round(($km_depuis_vidange / $intervalle) * 100));
?>
<!-- --- AFFICHAGE / VUE --- -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hub Auto — Gestionnaire de Flotte & Dépenses</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="app-header">
    <div class="logo">🚗 Hub Auto</div>
    <div class="vehicule-switcher">
        <form method="GET" action="index.php">
            <label for="v-select">Véhicule actif :</label>
            <select name="vehicule_id" id="v-select" onchange="this.form.submit()">
                <?php foreach ($vehicules as $v): ?>
                    <option value="<?= $v['id'] ?>" <?= $v['id'] === $current_id ? 'selected' : '' ?>>
                        <?= htmlspecialchars($v['marque'] . ' ' . $v['modele'] . ' (' . $v['immatriculation'] . ')') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
</header>

<main class="container">

<?php if (!$current_vehicule): ?>
    <div class="card empty-state">
        <h2>Aucun véhicule enregistré</h2>
        <p>Ajoutez votre premier véhicule pour démarrer le suivi.</p>
    </div>
<?php else: ?>

    <section class="kpi-grid">
        <div class="kpi-card">
            <span class="kpi-title">Compteur Actuel</span>
            <span class="kpi-value"><?= number_format($current_vehicule['kilometrage'], 0, ',', ' ') ?> km</span>
            <span class="kpi-sub"><?= htmlspecialchars($current_vehicule['immatriculation']) ?></span>
        </div>
        <div class="kpi-card">
            <span class="kpi-title">Consommation Moyenne</span>
            <span class="kpi-value"><?= $conso_moyenne > 0 ? $conso_moyenne . ' L/100' : 'N/A' ?></span>
            <span class="kpi-sub">Calculé sur <?= count($pleins) ?> pleins</span>
        </div>
        <div class="kpi-card">
            <span class="kpi-title">Budget Total Carburant</span>
            <span class="kpi-value"><?= number_format($total_depense_carburant, 2, ',', ' ') ?> €</span>
            <span class="kpi-sub"><?= round($total_litres, 1) ?> litres cumulés</span>
        </div>
        <div class="kpi-card">
            <span class="kpi-title">Entretien & Pièces</span>
            <span class="kpi-value"><?= number_format($total_pieces, 2, ',', ' ') ?> €</span>
            <span class="kpi-sub"><?= count($pieces) ?> opérations</span>
        </div>
    </section>

    <section class="card alert-section">
        <div class="alert-header">
            <h3>Échéance Vidange Moteur</h3>
            <span class="badge-km"><?= $km_depuis_vidange ?> km parcourus / <?= $intervalle ?> km prévus</span>
        </div>
        <div class="progress-bar">
            <div class="progress-fill <?= $pourcent_vidange >= 90 ? 'critical' : ($pourcent_vidange >= 75 ? 'warning' : 'ok') ?>" style="width: <?= $pourcent_vidange ?>%;"></div>
        </div>
    </section>

    <div class="grid-2col">
        <section class="card">
            <h3>⛽ Suivi Carburant</h3>
            
            <form method="POST" action="index.php" class="form-compact">
                <input type="hidden" name="action" value="ajouter_plein">
                <input type="hidden" name="vehicule_id" value="<?= $current_id ?>">
                
                <div class="form-row">
                    <input type="date" name="date_plein" value="<?= date('Y-m-d') ?>" required>
                    <input type="number" name="kilometrage" placeholder="Kilométrage" min="<?= $current_vehicule['kilometrage'] ?>" required>
                </div>
                <div class="form-row">
                    <input type="number" step="0.01" name="litres" id="input-litres" placeholder="Litres" required>
                    <input type="number" step="0.01" name="prix_total" id="input-prix" placeholder="Prix Total (€)" required>
                </div>
                <div class="calc-indicator">
                    Prix calculé au litre : <strong id="live-prix-litre">-</strong> €/L
                </div>
                <button type="submit" class="btn btn-primary">Ajouter le plein</button>
            </form>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Km</th>
                        <th>Litres</th>
                        <th>Total</th>
                        <th>€/L</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pleins)): ?>
                        <tr><td colspan="6" class="text-center">Aucun plein enregistré.</td></tr>
                    <?php else: ?>
                        <?php foreach ($pleins as $p): ?>
                            <tr>
                                <td><?= htmlspecialchars($p['date_plein']) ?></td>
                                <td><?= number_format($p['kilometrage'], 0, ',', ' ') ?></td>
                                <td><?= $p['litres'] ?> L</td>
                                <td><?= number_format($p['prix_total'], 2) ?> €</td>
                                <td><?= $p['prix_litre'] ?></td>
                                <td>
                                    <form method="POST" action="index.php" onsubmit="return confirm('Supprimer ce plein ?');">
                                        <input type="hidden" name="action" value="supprimer_plein">
                                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                        <input type="hidden" name="vehicule_id" value="<?= $current_id ?>">
                                        <button type="submit" class="btn-delete">×</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

        <section class="card">
            <h3>🔧 Pièces & Entretiens</h3>
            
            <form method="POST" action="index.php" class="form-compact">
                <input type="hidden" name="action" value="ajouter_piece">
                <input type="hidden" name="vehicule_id" value="<?= $current_id ?>">

                <div class="form-row">
                    <input type="text" name="nom" placeholder="Désignation (ex: Bougies)" required>
                    <input type="text" name="reference" placeholder="Réf (optionnel)">
                </div>
                <div class="form-row">
                    <input type="number" step="0.01" name="prix" placeholder="Prix TTC (€)" required>
                    <select name="statut">
                        <option value="commandee">Commandée</option>
                        <option value="recue">Reçue</option>
                        <option value="montee">Montée</option>
                    </select>
                </div>
                <input type="date" name="date_operation" value="<?= date('Y-m-d') ?>" required>
                <button type="submit" class="btn btn-secondary">Enregistrer l'opération</button>
            </form>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Pièce</th>
                        <th>Prix</th>
                        <th>Statut</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pieces)): ?>
                        <tr><td colspan="5" class="text-center">Aucune pièce répertoriée.</td></tr>
                    <?php else: ?>
                        <?php foreach ($pieces as $pc): ?>
                            <tr>
                                <td><?= htmlspecialchars($pc['date_operation']) ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($pc['nom']) ?></strong>
                                    <?php if ($pc['reference']): ?>
                                        <small class="d-block">(<?= htmlspecialchars($pc['reference']) ?>)</small>
                                    <?php endif; ?>
                                </td>
                                <td><?= number_format($pc['prix'], 2) ?> €</td>
                                <td><span class="status-badge status-<?= $pc['statut'] ?>"><?= ucfirst($pc['statut']) ?></span></td>
                                <td>
                                    <form method="POST" action="index.php" onsubmit="return confirm('Supprimer cette pièce ?');">
                                        <input type="hidden" name="action" value="supprimer_piece">
                                        <input type="hidden" name="id" value="<?= $pc['id'] ?>">
                                        <input type="hidden" name="vehicule_id" value="<?= $current_id ?>">
                                        <button type="submit" class="btn-delete">×</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </div>

<?php endif; ?>

    <section class="card add-vehicule-section">
        <h3>➕ Ajouter un nouveau véhicule au parc</h3>
        <form method="POST" action="index.php" class="form-inline">
            <input type="hidden" name="action" value="ajouter_vehicule">
            <input type="text" name="immatriculation" placeholder="Immatriculation (ex: AA-123-BB)" required>
            <input type="text" name="marque" placeholder="Marque (ex: Peugeot)" required>
            <input type="text" name="modele" placeholder="Modèle (ex: 208)" required>
            <input type="number" name="kilometrage" placeholder="Km actuel" required>
            <input type="number" name="km_derniere_vidange" placeholder="Km dernière vidange" required>
            <button type="submit" class="btn btn-outline">Créer la fiche</button>
        </form>
    </section>

</main>

<script src="script.js"></script>
</body>
</html>