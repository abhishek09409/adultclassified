<?php /** @var list<array<string, mixed>> $rows */ /** @var list<array<string, mixed>> $states */ ?>
<form method="post" class="row g-2 mb-4">
    <?= csrf_field() ?>
    <div class="col-md-4">
        <label for="state_id">State</label>
        <select class="form-select" id="state_id" data-city-target="city_id" data-locality-target="unused">
            <option value="">Select</option>
            <?php foreach ($states as $state): ?><option value="<?= e((string) $state['id']) ?>"><?= e((string) $state['name']) ?></option><?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-4"><label for="city_id">City</label><select class="form-select" id="city_id" name="city_id" data-locality-target="unused" required><option value="">Select a state</option></select></div>
    <div class="col-md-4"><label for="name">Locality</label><input class="form-control" id="name" name="name" required></div>
    <div class="col-12"><button class="btn btn-brand" type="submit">Add locality</button></div>
</form>
<table class="table"><thead><tr><th>Locality</th><th>Status</th></tr></thead><tbody>
<?php foreach ($rows as $row): ?><tr><td><?= e((string) ($row['name'] ?? '')) ?></td><td><?= e((string) ($row['status'] ?? '')) ?></td></tr><?php endforeach; ?>
</tbody></table>
