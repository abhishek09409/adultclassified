<?php

declare(strict_types=1);

/** @var list<array<string, mixed>> $states */
/** @var list<array<string, mixed>> $categories */
/** @var string $prefix */
?>
<div class="row g-3 location-selector">
    <div class="col-md-6 col-lg-3">
        <label for="<?= e($prefix) ?>-category">Category</label>
        <select class="form-select" id="<?= e($prefix) ?>-category" name="category_id">
            <option value="">Any category</option>
            <?php foreach ($categories as $category): ?>
                <?php if (($category['status'] ?? 'active') !== 'active') { continue; } ?>
                <option value="<?= e((string) $category['id']) ?>"><?= e((string) $category['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-6 col-lg-3">
        <label for="<?= e($prefix) ?>-state">State</label>
        <select class="form-select" id="<?= e($prefix) ?>-state" name="state_id" data-city-target="<?= e($prefix) ?>-city" data-locality-target="<?= e($prefix) ?>-locality">
            <option value="">Select State</option>
            <?php foreach ($states as $state): ?>
                <?php if (($state['status'] ?? 'active') !== 'active') { continue; } ?>
                <option value="<?= e((string) $state['id']) ?>"><?= e((string) $state['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-6 col-lg-3">
        <label for="<?= e($prefix) ?>-city">City</label>
        <select class="form-select" id="<?= e($prefix) ?>-city" name="city_id" data-locality-target="<?= e($prefix) ?>-locality" data-placeholder="Select City" disabled>
            <option value="">Select City</option>
        </select>
    </div>
    <div class="col-md-6 col-lg-3">
        <label for="<?= e($prefix) ?>-locality">Locality</label>
        <select class="form-select" id="<?= e($prefix) ?>-locality" name="location_id" data-placeholder="Select Locality" disabled>
            <option value="">Select Locality</option>
        </select>
    </div>
</div>
