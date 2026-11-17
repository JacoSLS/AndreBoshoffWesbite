<?php
require_once __DIR__ . '/includes/editor.php';

bww_run_editor('home', [
  [
    'key' => 'meta', 'path' => '', 'title' => 'Page title for Google',
    'intro' => 'What shows in a Google result and the browser tab.',
    'fields' => [
      ['key' => 'meta_title', 'label' => 'Page title', 'max' => 120],
      ['key' => 'meta_desc',  'label' => 'Description for search results', 'type' => 'textarea', 'rows' => 3,
       'hint' => 'Around 150 characters. This is the grey text under the blue link on Google.'],
    ],
  ],
  [
    'key' => 'banner', 'title' => 'The banner at the top of the home page',
    'intro' => 'The wide picture that fills the top of the home page. Replace it here whenever you want a new one.',
    'fields' => [
      ['key' => 'image', 'label' => 'Banner picture', 'type' => 'image',
       'default' => 'img/hero-banner.jpg',
       'hint' => 'A wide, landscape picture. Around 1800 pixels across works best. This is the full-width image at the very top of the home page.'],
      ['key' => 'image_mobile', 'label' => 'Separate picture for phones (optional)', 'type' => 'image',
       'hint' => 'Only needed if the wide banner feels too small on a phone. Upload a taller, narrower version here and phones will use it instead. Leave empty to use the same picture everywhere.'],
      ['key' => 'heading', 'label' => 'Main page heading for Google (not shown on screen)', 'max' => 160,
       'placeholder' => 'Freeland Well and Water Services — well pump, water testing and water treatment services in northern Maryland',
       'hint' => 'Search engines and screen readers read this as the page\'s main heading, because the banner picture itself is not text. Leave empty to use the wording shown in grey.'],
      ['key' => 'alt', 'label' => 'Short description of the picture', 'max' => 160,
       'hint' => 'Read aloud to visitors who cannot see images, and used by Google. One short sentence, e.g. "Freeland Well and Water Services truck on a job in Maryland."'],
    ],
  ],
  [
    'key' => 'trust', 'title' => 'The four points strip',
    'intro' => 'The row of short selling points directly under the top section.',
    'fields' => [
      ['key' => 'show', 'label' => 'Show this strip', 'type' => 'check'],
      ['key' => 'items', 'label' => 'Points', 'type' => 'repeat', 'hint' => 'Four works best. Each needs a heading and one line.',
       'fields' => [
         ['key' => 'title', 'label' => 'Heading', 'placeholder' => 'e.g. No upselling'],
         ['key' => 'text',  'label' => 'One line', 'type' => 'textarea', 'rows' => 2],
       ]],
    ],
  ],
  [
    'key' => 'services_section', 'title' => 'The services section',
    'intro' => 'Your service cards, copied from the Services tab without the photos. Change the wording, the order or add a service under Services and this section updates by itself.',
    'fields' => [
      ['key' => 'show',  'label' => 'Show this section', 'type' => 'check'],
      ['key' => 'icons', 'label' => 'Show the small icon on each card', 'type' => 'check', 'default' => true],
      ['key' => 'order', 'label' => 'Which section comes first', 'type' => 'select', 'default' => 'services_first',
       'options' => ['services_first' => 'Services first, then "Why homeowners call us back"',
                     'why_first'      => '"Why homeowners call us back" first, then Services']],
    ],
  ],
  [
    'key' => 'why', 'title' => 'The "why homeowners call us back" section',
    'intro' => 'Your pitch, the tick list beside it, and the photo.',
    'fields' => [
      ['key' => 'show',    'label' => 'Show this section', 'type' => 'check'],
      ['key' => 'eyebrow', 'label' => 'Small line above the heading', 'max' => 60],
      ['key' => 'heading', 'label' => 'Heading', 'max' => 120],
      ['key' => 'body',    'label' => 'Paragraphs', 'type' => 'textarea', 'rows' => 7],
      ['key' => 'points',  'label' => 'Tick list', 'type' => 'repeat',
       'hint' => 'The bold part is the promise, the rest explains it.',
       'fields' => [
         ['key' => 'strong', 'label' => 'Bold part', 'placeholder' => 'e.g. Fair, transparent pricing.'],
         ['key' => 'text',   'label' => 'Rest of the line'],
       ]],
      ['key' => 'cta_label', 'label' => 'Button label', 'max' => 40, 'hint' => 'Links to About Us. Leave empty to hide.'],
      ['key' => 'image',     'label' => 'Photo', 'type' => 'image'],
    ],
  ],
  [
    'key' => 'area', 'title' => 'The "where we work" section',
    'intro' => 'The counties themselves are edited under Contact &amp; Settings, so they stay the same everywhere on the site.',
    'fields' => [
      ['key' => 'show',    'label' => 'Show this section', 'type' => 'check'],
      ['key' => 'eyebrow', 'label' => 'Small line above the heading', 'max' => 60],
      ['key' => 'heading', 'label' => 'Heading', 'max' => 120],
      ['key' => 'text',    'label' => 'Line under the heading', 'type' => 'textarea', 'rows' => 2],
      ['key' => 'extra_tag', 'label' => 'Extra area tag', 'max' => 60, 'hint' => 'An additional pill after the counties, e.g. "Freeland & surrounds".'],
    ],
  ],
  [
    'key' => 'stats', 'title' => 'The three numbers',
    'intro' => 'The big figures at the bottom of the home page.',
    'fields' => [
      ['key' => 'show',  'label' => 'Show the numbers', 'type' => 'check'],
      ['key' => 'items', 'label' => 'Numbers', 'type' => 'repeat',
       'fields' => [
         ['key' => 'value', 'label' => 'Number', 'placeholder' => 'e.g. 24/7'],
         ['key' => 'label', 'label' => 'What it means', 'placeholder' => 'e.g. Emergency availability'],
       ]],
    ],
  ],
], [
  'admin_page' => 'page-home',
  'title'      => 'Home page',
  'intro'      => 'Everything on your home page, top to bottom.',
  'view'       => '../index.php',
]);
