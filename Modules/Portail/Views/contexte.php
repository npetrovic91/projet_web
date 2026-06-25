<?php
$data = $data ?? [];
$contexte = $data['contexte'] ?? [];
$societes = $data['societes'] ?? [];
$concessions = $data['concessions'] ?? [];
$marques = $data['marques'] ?? [];
$services = $data['services'] ?? [];
$equipes = $data['equipes'] ?? [];
$regles = $data['regles'] ?? [];
?>
<section class="page-header">
    <h1>Contexte actif</h1>
    <p>Sélection de la société, de la concession, de la marque, du service et de l’équipe actifs.</p>
</section>

<form method="post" action="<?= url('/portail/contexte/update') ?>" class="card" id="form-contexte-actif">
    <?= $csrfField ?? csrf_field() ?>

    <div class="grid grid-2">
        <label>
            <span>Société d’appartenance active</span>
            <select name="societe_id" id="sel-societes" required>
                <option value="">— Sélectionner —</option>
                <?php foreach ($societes as $societe): ?>
                    <option value="<?= (int)$societe['soc_id'] ?>" <?= (int)($contexte['societe_id'] ?? 0) === (int)$societe['soc_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars(($societe['soc_code'] ? $societe['soc_code'] . ' — ' : '') . $societe['soc_nom'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            <span>Concession active</span>
            <select name="concession_id" id="sel-concessions" <?= !empty($regles['concession_verrouillee_si_unique']) ? 'data-verrouille="1"' : '' ?> required>
                <option value="">— Sélectionner —</option>
                <?php foreach ($concessions as $concession): ?>
                    <option value="<?= (int)$concession['soc_id'] ?>" <?= (int)($contexte['concession_id'] ?? 0) === (int)$concession['soc_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars(($concession['soc_code'] ? $concession['soc_code'] . ' — ' : '') . $concession['soc_nom'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            <span>Marque active</span>
            <select name="marque_id" id="sel-marques" <?= !empty($regles['marque_verrouillee_si_unique']) ? 'data-verrouille="1"' : '' ?>>
                <option value="">Toutes les marques autorisées</option>
                <?php foreach ($marques as $marque): ?>
                    <option value="<?= (int)$marque['soc_id'] ?>" <?= (int)($contexte['marque_id'] ?? 0) === (int)$marque['soc_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars(($marque['soc_code'] ? $marque['soc_code'] . ' — ' : '') . $marque['soc_nom'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            <span>Service actif</span>
            <select name="service_id" id="sel-services" <?= !empty($regles['service_verrouille_si_unique']) ? 'data-verrouille="1"' : '' ?>>
                <option value="">Aucun service spécifique</option>
                <?php foreach ($services as $service): ?>
                    <option value="<?= (int)$service['srv_id'] ?>" <?= (int)($contexte['service_id'] ?? 0) === (int)$service['srv_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars(($service['srv_code'] ? $service['srv_code'] . ' — ' : '') . $service['srv_nom'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            <span>Équipe active</span>
            <select name="equipe_id" id="sel-equipes" <?= !empty($regles['equipe_verrouillee_si_unique']) ? 'data-verrouille="1"' : '' ?>>
                <option value="">Aucune équipe spécifique</option>
                <?php foreach ($equipes as $equipe): ?>
                    <option value="<?= (int)$equipe['equ_id'] ?>" <?= (int)($contexte['equipe_id'] ?? 0) === (int)$equipe['equ_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars(($equipe['equ_code'] ? $equipe['equ_code'] . ' — ' : '') . $equipe['equ_nom'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>

    <div class="actions">
        <button type="submit" class="btn btn-primary">Appliquer le contexte</button>
        <a href="<?= url('/portail') ?>" class="btn">Retour au portail</a>
    </div>
</form>

<section class="card">
    <h2>Règles appliquées</h2>
    <ul>
        <li>Le choix de société et de concession est obligatoire après connexion.</li>
        <li>Une marque ne peut être sélectionnée que si la concession la représente.</li>
        <li>Les services et équipes proposés sont limités aux affectations actives de l’utilisateur.</li>
        <li>Chaque changement est écrit en session, dans la session persistante et dans l’historique des contextes.</li>
    </ul>
</section>

<script<?= $nonceAttr ?? '' ?>>
(function () {
    const concession = document.getElementById('sel-concessions');
    const service = document.getElementById('sel-services');
    const endpoint = '<?= url('/portail/contexte/options.json') ?>';

    async function chargerOptions() {
        const params = new URLSearchParams(new FormData(document.getElementById('form-contexte-actif')));
        const res = await fetch(endpoint + '?' + params.toString(), {headers: {'X-Requested-With': 'XMLHttpRequest'}});
        if (!res.ok) return;
        const payload = await res.json();
        if (!payload.success) return;
        remplacerOptions('sel-marques', payload.data.marques || [], 'soc_id', 'soc_nom', 'Toutes les marques autorisées');
        remplacerOptions('sel-equipes', payload.data.equipes || [], 'equ_id', 'equ_nom', 'Aucune équipe spécifique');
    }

    function remplacerOptions(id, rows, key, label, emptyLabel) {
        const select = document.getElementById(id);
        if (!select) return;
        const current = select.value;
        select.innerHTML = '';
        const empty = document.createElement('option');
        empty.value = '';
        empty.textContent = emptyLabel;
        select.appendChild(empty);
        rows.forEach(row => {
            const option = document.createElement('option');
            option.value = row[key];
            option.textContent = (row.soc_code || row.equ_code || '') ? ((row.soc_code || row.equ_code) + ' — ' + row[label]) : row[label];
            if (String(option.value) === current) option.selected = true;
            select.appendChild(option);
        });
    }

    concession?.addEventListener('change', chargerOptions);
    service?.addEventListener('change', chargerOptions);
})();
</script>
