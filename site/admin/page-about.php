<?php
require_once __DIR__ . '/includes/editor.php';

bww_run_editor('about', [
  [
    'key' => 'meta', 'path' => '', 'title' => 'Page title for Google',
    'intro' => 'What shows in a Google result and the browser tab.',
    'fields' => [
      ['key' => 'meta_title', 'label' => 'Page title', 'max' => 120],
      ['key' => 'meta_desc',  'label' => 'Description for search results', 'type' => 'textarea', 'rows' => 3],
    ],
  ],
  [
    'key' => 'hero', 'title' => 'Top of the page',
    'fields' => [
      ['key' => 'eyebrow', 'label' => 'Small line above the heading', 'max' => 60],
      ['key' => 'heading', 'label' => 'Main heading', 'max' => 160],
      ['key' => 'text',    'label' => 'Intro paragraph', 'type' => 'textarea', 'rows' => 3],
    ],
  ],
  [
    'key' => 'story', 'title' => 'Your story',
    'intro' => 'How the business started and how it is run.',
    'fields' => [
      ['key' => 'show',    'label' => 'Show this section', 'type' => 'check'],
      ['key' => 'eyebrow', 'label' => 'Small line above the heading', 'max' => 60],
      ['key' => 'heading', 'label' => 'Heading', 'max' => 140],
      ['key' => 'body',    'label' => 'Your story', 'type' => 'textarea', 'rows' => 10],
      ['key' => 'image',   'label' => 'Photo', 'type' => 'image'],
      ['key' => 'image_alt', 'label' => 'What the photo shows', 'max' => 160,
       'hint' => 'One short sentence, read aloud to visitors who cannot see the photo and used by Google. Leave empty to use the heading.'],
    ],
  ],
  [
    'key' => 'values', 'title' => 'How we work',
    'intro' => 'The cards showing what you will not compromise on. Add or delete as many as you like.',
    'fields' => [
      ['key' => 'show',    'label' => 'Show this section', 'type' => 'check'],
      ['key' => 'eyebrow', 'label' => 'Small line above the heading', 'max' => 60],
      ['key' => 'heading', 'label' => 'Heading', 'max' => 140],
      ['key' => 'items',   'label' => 'Cards', 'type' => 'repeat',
       'fields' => [
         ['key' => 'title', 'label' => 'Card heading'],
         ['key' => 'text',  'label' => 'Card text', 'type' => 'textarea', 'rows' => 4],
         ['key' => 'icon',  'label' => 'Picture', 'type' => 'icon'],
       ]],
    ],
  ],
  [
    'key' => 'who', 'title' => 'Who we help',
    'fields' => [
      ['key' => 'show',    'label' => 'Show this section', 'type' => 'check'],
      ['key' => 'eyebrow', 'label' => 'Small line above the heading', 'max' => 60],
      ['key' => 'heading', 'label' => 'Heading', 'max' => 140],
      ['key' => 'body',    'label' => 'Paragraphs', 'type' => 'textarea', 'rows' => 7],
      ['key' => 'points',  'label' => 'Tick list', 'type' => 'list', 'placeholder' => 'e.g. No water, or water that has slowed to a trickle'],
      ['key' => 'image',   'label' => 'Photo', 'type' => 'image'],
      ['key' => 'image_alt', 'label' => 'What the photo shows', 'max' => 160,
       'hint' => 'One short sentence, read aloud to visitors who cannot see the photo and used by Google. Leave empty to use the heading.'],
    ],
  ],
], [
  'admin_page' => 'page-about',
  'title'      => 'About Us page',
  'intro'      => 'Every heading, paragraph and photo on your About page.',
  'view'       => '../about.php',
]);
