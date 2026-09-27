<?php /** @var list<array<string, mixed>> $reports */ /** @var list<array<string, mixed>> $pending */ /** @var list<array<string, mixed>> $suspended */ /** @var list<array<string, mixed>> $takedowns */ /** @var list<array<string, mixed>> $failures */ /** @var list<array<string, mixed>> $audit */ /** @var array<string, string> $filters */ ?>
<form method="get" class="row g-2 mb-4">
    <div class="col-md-3"><label for="from">From</label><input class="form-control" id="from" type="date" name="from" value="<?= e($filters['from']) ?>"></div>
    <div class="col-md-3"><label for="to">To</label><input class="form-control" id="to" type="date" name="to" value="<?= e($filters['to']) ?>"></div>
    <div class="col-md-3"><label for="status">Status</label><input class="form-control" id="status" name="status" value="<?= e($filters['status']) ?>"></div>
    <div class="col-md-3"><label for="reason">Reason</label><input class="form-control" id="reason" name="reason" value="<?= e($filters['reason']) ?>"></div>
    <div class="col-12"><button class="btn btn-outline-dark" type="submit">Filter</button></div>
</form>
<h2 class="h5">Reported listings</h2>
<ul><?php foreach ($reports as $row): ?><li><?= e((string) $row['title']) ?> · <?= e((string) $row['reason']) ?> · <?= e((string) $row['status']) ?> · <?= e((string) $row['category_name']) ?> · <?= e((string) $row['state_name']) ?></li><?php endforeach; ?></ul>
<h2 class="h5">Pending moderation</h2>
<ul><?php foreach ($pending as $row): ?><li><a href="/admin/listings/<?= e((string) $row['id']) ?>/edit"><?= e((string) $row['title']) ?></a></li><?php endforeach; ?></ul>
<h2 class="h5">Suspended listings</h2>
<ul><?php foreach ($suspended as $row): ?><li><?= e((string) $row['title']) ?></li><?php endforeach; ?></ul>
<h2 class="h5">Takedown requests</h2>
<ul><?php foreach ($takedowns as $row): ?><li><?= e((string) $row['title']) ?> · <?= e((string) $row['status']) ?></li><?php endforeach; ?></ul>
<h2 class="h5">Automation compliance failures</h2>
<ul><?php foreach ($failures as $row): ?><li><?= e((string) $row['action']) ?> · <?= e((string) $row['message']) ?></li><?php endforeach; ?></ul>
<h2 class="h5">Recent audit events</h2>
<ul><?php foreach ($audit as $row): ?><li><?= e((string) $row['action']) ?> · <?= e((string) $row['created_at']) ?></li><?php endforeach; ?></ul>
