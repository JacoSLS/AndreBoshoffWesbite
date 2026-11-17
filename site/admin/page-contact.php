<?php
require_once __DIR__ . '/includes/editor.php';

bww_run_editor('contact_page', [
  [
    'key' => 'meta', 'path' => '', 'title' => 'Page title for Google',
    'fields' => [
      ['key' => 'meta_title', 'label' => 'Page title', 'max' => 120],
      ['key' => 'meta_desc',  'label' => 'Description for search results', 'type' => 'textarea', 'rows' => 3],
    ],
  ],
  [
    'key' => 'hero', 'title' => 'Top of the page',
    'fields' => [
      ['key' => 'eyebrow', 'label' => 'Small line above the heading', 'max' => 60],
      ['key' => 'heading', 'label' => 'Main heading', 'max' => 140],
      ['key' => 'text',    'label' => 'Intro paragraph', 'type' => 'textarea', 'rows' => 3],
    ],
  ],
  [
    'key' => 'headings', 'path' => '', 'title' => 'Section headings',
    'intro' => 'Your phone number, email, hours and service areas themselves live under Contact & Settings so they stay identical across the whole site.',
    'fields' => [
      ['key' => 'details_heading',  'label' => 'Heading above your contact details', 'max' => 60],
      ['key' => 'hours_heading',    'label' => 'Heading above your opening hours', 'max' => 60],
      ['key' => 'emergency_label',  'label' => 'Bold lead-in on the emergency note', 'max' => 60],
    ],
  ],
  [
    'key' => 'form', 'path' => '', 'title' => 'The enquiry form',
    'intro' => 'Where the form sends to is set under Contact & Settings.',
    'fields' => [
      ['key' => 'show_form',    'label' => 'Show the enquiry form', 'type' => 'check',
       'hint' => 'Switch off and the page becomes phone and email only.'],
      ['key' => 'form_heading', 'label' => 'Form heading', 'max' => 60],
      ['key' => 'form_intro',   'label' => 'Line under the form heading', 'type' => 'textarea', 'rows' => 2],
      ['key' => 'form_success', 'label' => 'Thank-you message after sending', 'type' => 'textarea', 'rows' => 2],
      ['key' => 'form_note',    'label' => 'Small print under the send button', 'type' => 'textarea', 'rows' => 2],
      ['key' => 'message_placeholder', 'label' => 'Example text inside the message box', 'type' => 'textarea', 'rows' => 2],
    ],
  ],
  [
    'key' => 'labels', 'title' => 'Form field labels',
    'intro' => 'Rename any box on the form.',
    'fields' => [
      ['key' => 'name',    'label' => 'Name box', 'max' => 60],
      ['key' => 'phone',   'label' => 'Phone box', 'max' => 60],
      ['key' => 'email',   'label' => 'Email box', 'max' => 60],
      ['key' => 'service', 'label' => 'Service dropdown', 'max' => 80],
      ['key' => 'message', 'label' => 'Message box', 'max' => 80],
      ['key' => 'submit',  'label' => 'Send button', 'max' => 40],
    ],
  ],
], [
  'admin_page' => 'page-contact',
  'title'      => 'Contact Us page',
  'intro'      => 'The wording on your contact page and the enquiry form.',
  'view'       => '../contact.php',
]);
