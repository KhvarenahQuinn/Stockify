<?php

/**
 * The JS i18n dictionary.
 *
 * Blade renders this before the Vite bundle so every UI module can call
 * `window.t()` without importing a translator or waiting for an async load.
 * The keys are the English source strings, exactly as they appear in the
 * JavaScript modules.
 */
$jsStrings = [
    '":label" is already on this list.',
    ':available :unit on hand is at or below the :min reorder point for this product.',
    ':count line still needs a physical count. Fill every line in, or remove the ones you did not count.',
    ':count lines still need a physical count. Fill every line in, or remove the ones you did not count.',
    ':count product available.',
    ':count products available.',
    ':count selected',
    'A physical count cannot be negative.',
    'Add',
    'Added values',
    'Add at least one product before submitting the count.',
    'Confirm count and send to a manager',
    'Count matches the request. This goes to a manager for approval as :requested :unit.',
    'Enter what is physically on the shelf.',
    'Error',
    'Flag discrepancy and send to a manager',
    'found',
    'Hide password',
    'No products match that filter.',
    'No products match.',
    'No reason given',
    'Not counted',
    'Nothing selected yet',
    'Notice',
    'on hand',
    'On sheet',
    'Only .csv, .xlsx and .xls files are accepted.',
    'Only :available :unit available.',
    'Only :available :unit on hand - :short short of the :requested requested. Lower the quantity or pick another product.',
    'over',
    'Pick a JPEG, PNG or WebP file.',
    'Preparing your :format download...',
    'Preparing...',
    'Record the count',
    'Remove :label',
    'Removed values',
    'Say what you found before flagging a discrepancy.',
    'short',
    'short of',
    'Show password',
    'Showing the first :limit of :total matches.',
    'Success',
    'surplus',
    'system stock',
    'That file could not be read.',
    'That file is :size MB. The limit is :limit MB.',
    'That image is :size MB. The limit is 2 MB.',
    'This will increase stock on hand.',
    'This will reduce stock on hand.',
    'Warning',
    'What changed',
    'You counted :counted :unit, :variance :direction the requested :requested. A manager decides what happens next.',
];
?>
<script>
    window.i18n = @json(collect($jsStrings)->mapWithKeys(fn ($key) => [$key => __($key)])->all());
</script>
