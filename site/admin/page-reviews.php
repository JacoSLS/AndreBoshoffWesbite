<?php
require_once __DIR__ . '/includes/editor.php';

bww_run_editor('reviews_page', [
  [
    'key' => 'meta', 'path' => '', 'title' => 'Page title for Google',
    'fields' => [
      ['key' => 'meta_title', 'label' => 'Page title', 'max' => 120, 'placeholder' => 'Customer Reviews'],
      ['key' => 'meta_desc',  'label' => 'Description for search results', 'type' => 'textarea', 'rows' => 3],
    ],
  ],
  [
    'key' => 'hero', 'title' => 'Top of the page',
    'fields' => [
      ['key' => 'eyebrow', 'label' => 'Small line above the heading', 'max' => 60, 'placeholder' => 'Customer Testimonials'],
      ['key' => 'heading', 'label' => 'Main heading', 'max' => 140, 'placeholder' => 'What Our Clients Are Saying'],
      ['key' => 'text',    'label' => 'Intro paragraph (optional)', 'type' => 'textarea', 'rows' => 3],
    ],
  ],
  [
    'key' => 'list', 'path' => '', 'title' => 'Reviews',
    'intro' => 'Reviews show on the page in this order. The Reviews page appears in the menu once there is at least one review.',
    'fields' => [
      ['key' => 'layout', 'label' => 'How reviews are laid out', 'type' => 'select', 'default' => 'grid',
       'options' => ['grid' => 'Side by side — rows of 3 (phones always show a list)', 'list' => 'List — one under the other']],
      ['key' => 'trim_chars', 'label' => 'Characters shown before "…"', 'max' => 4, 'default' => 30,
       'hint' => 'Reviews longer than this are shortened with "…" and open in full when tapped. A smaller number makes the cards shorter. Use 0 to always show the whole review.'],
      ['key' => 'items', 'label' => 'Reviews', 'type' => 'repeat', 'max_rows' => 200,
       'fields' => [
         ['key' => 'quote', 'label' => 'What the customer said', 'type' => 'textarea', 'rows' => 5],
         ['key' => 'name',  'label' => 'Customer name (optional)', 'placeholder' => 'e.g. William L.'],
       ]],
    ],
  ],
], [
  'admin_page' => 'page-reviews',
  'title'      => 'Reviews page',
  'intro'      => 'Add, change or remove customer reviews.',
  'view'       => '../reviews.php',
]);
