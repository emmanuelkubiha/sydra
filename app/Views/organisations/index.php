<?php

declare(strict_types=1);

$rows = $rows ?? [];
?>
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card card-outline card-primary">
            <div class="card-header"><h3 class="card-title">Nouvelle organisation</h3></div>
            <div class="card-body">
                <?php if (!empty($success)): ?><div class="alert alert-success"><?= htmlspecialchars((string) $success, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                <?php if (!empty($error)): ?><div class="alert alert-danger"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                <form method="post" action="?r=organisations/store" enctype="multipart/form-data">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(App\Core\Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
                    <div class="mb-3">
                        <label class="form-label">Nom</label>
                        <input class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input class="form-control" name="email" type="email">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email de contact</label>
                        <input class="form-control" name="contact_email" type="email">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Site web</label>
                        <input class="form-control" name="website" type="url" placeholder="https://...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Logo (URL)</label>
                        <input class="form-control" name="logo_url" placeholder="https://.../logo.png">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Logo (fichier)</label>
                        <input class="form-control" name="logo_file" type="file" accept=".png,.jpg,.jpeg,.webp,.svg">
                    </div>
                    <button type="submit" class="btn btn-primary">Creer</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card card-outline card-secondary">
            <div class="card-header"><h3 class="card-title">Liste des organisations</h3></div>
            <div class="card-body">
                <table id="table_organisations" class="table table-bordered table-striped">
                    <thead><tr><th>ID</th><th>Logo</th><th>Nom</th><th>Email</th><th>Contact</th><th>Site web</th><th>Statut</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td><?= (int) $row['id'] ?></td>
                                <td>
                                    <?php if (!empty($row['logo_url'])): ?>
                                        <img src="<?= htmlspecialchars((string) $row['logo_url'], ENT_QUOTES, 'UTF-8') ?>" alt="logo" style="width:34px;height:34px;object-fit:cover;border-radius:50%;">
                                    <?php else: ?>
                                        <span class="badge text-bg-light">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) $row['email'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($row['contact_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <?php if (!empty($row['website'])): ?>
                                        <a href="<?= htmlspecialchars((string) $row['website'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">Visiter</a>
                                    <?php else: ?>-
                                    <?php endif; ?>
                                </td>
                                <td><?= ((int) $row['is_active'] === 1) ? 'Actif' : 'Inactif' ?></td>
                                <td>
                                    <form method="post" action="?r=organisations/toggle">
                                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(App\Core\Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                        <input type="hidden" name="is_active" value="<?= ((int) $row['is_active'] === 1) ? 0 : 1 ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-primary"><?= ((int) $row['is_active'] === 1) ? 'Desactiver' : 'Activer' ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
