<?php /** @var list<array<string, mixed>> $states */ /** @var list<array<string, mixed>> $rows */ ?>
<form method="post" class="row g-2 mb-4">
    <?= csrf_field() ?>
    <div class="col-md-4"><label for="state_id">State</label><select class="form-select" id="state_id" name="state_id"><?php foreach ($states as $state): ?><option value="<?= e((string) $state['id']) ?>"><?= e((string) $state['name']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-4"><label for="name">City</label><input class="form-control" id="name" name="name" required></div>
    <div class="col-md-4 d-flex align-items-end"><button class="btn btn-brand" type="submit">Add city</button></div>
</form>
<table class="table"><thead><tr><th>City</th><th>Status</th></tr></thead><tbody>
<?php foreach ($rows as $row): ?><tr><td><?= e((string) $row['name']) ?></td><td><?= e((string) $row['status']) ?></td></tr><?php endforeach; ?>
</tbody></table>
