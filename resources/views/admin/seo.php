<?php /** @var list<array<string, mixed>> $rows */ ?>
<table class="table"><thead><tr><th>Path</th><th>Title</th><th>Robots</th></tr></thead><tbody>
<?php foreach ($rows as $row): ?><tr><td><?= e((string) $row['path']) ?></td><td><?= e((string) $row['title']) ?></td><td><?= e((string) $row['robots']) ?></td></tr><?php endforeach; ?>
</tbody></table>
<p class="meta">Sitemaps: <a href="/sitemap.xml">/sitemap.xml</a></p>
