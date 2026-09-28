<?php

/** @var array<string, mixed>|null $listing */
/** @var list<array<string, mixed>> $categories */
/** @var list<array<string, mixed>> $states */
/** @var list<array<string, mixed>> $images */
$listing = $listing ?? null;
?>
<form method="post" action="<?= $listing ? '/admin/listings/' . e((string) $listing['id']) : '/admin/listings' ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <label for="title">Title</label>
    <input class="form-control mb-3" id="title" name="title" required value="<?= e((string) ($listing['title'] ?? '')) ?>">
    <label for="description">Description</label>
    <textarea class="form-control mb-3" id="description" name="description" rows="6" required><?= e((string) ($listing['description'] ?? '')) ?></textarea>
    <label for="availability_note">Availability note</label>
    <input class="form-control mb-3" id="availability_note" name="availability_note" value="<?= e((string) ($listing['availability_note'] ?? '')) ?>">
    <label for="age">Age</label>
    <input class="form-control mb-3" id="age" name="age" type="number" min="21" max="99" required value="<?= e((string) ($listing['age'] ?? '21')) ?>">
    <div class="row">
        <div class="col-md-3">
            <label for="category_id">Category</label>
            <select class="form-select mb-3" id="category_id" name="category_id" required>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= e((string) $category['id']) ?>"<?= (int) ($listing['category_id'] ?? 0) === (int) $category['id'] ? ' selected' : '' ?>><?= e((string) $category['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label for="state_id">State</label>
            <select class="form-select mb-3" id="state_id" name="state_id" data-city-target="city_id" data-locality-target="location_id" required>
                <option value="">Select</option>
                <?php foreach ($states as $state): ?>
                    <option value="<?= e((string) $state['id']) ?>"<?= (int) ($listing['state_id'] ?? 0) === (int) $state['id'] ? ' selected' : '' ?>><?= e((string) $state['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label for="city_id">City</label>
            <select class="form-select mb-3" id="city_id" name="city_id" data-locality-target="location_id" data-selected="<?= e((string) ($listing['city_id'] ?? '')) ?>" required>
                <option value="">Select a state</option>
            </select>
        </div>
        <div class="col-md-3">
            <label for="location_id">Locality</label>
            <select class="form-select mb-3" id="location_id" name="location_id" data-selected="<?= e((string) ($listing['location_id'] ?? '')) ?>" required>
                <option value="">Select a city</option>
            </select>
        </div>
    </div>
    <label for="status">Status</label>
    <select class="form-select mb-3" id="status" name="status">
        <?php foreach (['draft', 'pending', 'published', 'suspended'] as $status): ?>
            <option value="<?= e($status) ?>"<?= ($listing['status'] ?? 'draft') === $status ? ' selected' : '' ?>><?= e($status) ?></option>
        <?php endforeach; ?>
    </select>
    <label><input type="checkbox" name="is_featured" value="1"<?= !empty($listing['is_featured']) ? ' checked' : '' ?>> Featured</label>
    <label class="ms-3"><input type="checkbox" name="is_verified" value="1"<?= !empty($listing['is_verified']) ? ' checked' : '' ?>> Verified after a real check</label>
    <div class="mt-3">
        <label for="image">Image</label>
        <input class="form-control" id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp">
    </div>
    <button class="btn btn-brand mt-3" type="submit">Save</button>
</form>
<?php if ($listing): ?>
    <div class="mt-4 d-flex flex-wrap gap-2">
        <?php foreach (['publish', 'unpublish', 'suspend', 'restore', 'feature', 'unfeature', 'verify', 'unverify', 'delete'] as $action): ?>
            <form method="post" action="/admin/listings/<?= e((string) $listing['id']) ?>/action">
                <?= csrf_field() ?>
                <input type="hidden" name="listing_action" value="<?= e($action) ?>">
                <button class="btn btn-outline-dark btn-sm" type="submit"><?= e($action) ?></button>
            </form>
        <?php endforeach; ?>
    </div>
    <ul class="mt-3">
        <?php foreach ($images as $image): ?>
            <li><?= e((string) $image['filename']) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
