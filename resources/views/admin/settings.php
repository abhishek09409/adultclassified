<?php /** @var list<array<string, mixed>> $rows */ ?>
<table class="table"><thead><tr><th>Key</th><th>Value</th></tr></thead><tbody>
<?php foreach ($rows as $row): ?><tr><td><?= e((string) $row['setting_key']) ?></td><td><?= e((string) $row['setting_value']) ?></td></tr><?php endforeach; ?>
</tbody></table>
<p class="meta">Change automation values on the automation screen. Secrets stay in the environment file.</p>
