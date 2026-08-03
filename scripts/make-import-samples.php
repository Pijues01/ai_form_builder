<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\PhpWord;

/**
 * Generates the committed sample .docx/.xlsx files used to test Part C.
 *
 *   storage/import-samples/conference-feedback.docx    - Word layout
 *   storage/import-samples/registration-structured.xlsx - Excel layout A
 *   storage/import-samples/attendee-plain-header.xlsx   - Excel layout B
 *
 * Run: php scripts/make-import-samples.php
 */

require __DIR__.'/../vendor/autoload.php';

$dir = __DIR__.'/../storage/import-samples';
if (! is_dir($dir)) {
    mkdir($dir, 0755, true);
}

// ---------------------------------------------------------------- .docx
$phpWord = new PhpWord;

$phpWord->addTitleStyle(1, ['size' => 20, 'bold' => true]);
$phpWord->addTitleStyle(2, ['size' => 15, 'bold' => true]);

$section = $phpWord->addSection();

$section->addTitle('Conference Feedback Form', 1);
$section->addText('Tell us about your experience at the annual conference.');

$section->addTitle('About You', 2);
$section->addText('Full name (required)');
$section->addText('Email address');
$section->addText('Phone number');
$section->addText('Which city do you work in?');
$section->addListItem('Bengaluru', 0);
$section->addListItem('Mumbai', 0);
$section->addListItem('Delhi', 0);
$section->addListItem('Other', 0);

$section->addTitle('Feedback', 2);
$section->addText('How satisfied were you with the event?');
$section->addListItem('1 - Very dissatisfied', 0);
$section->addListItem('2 - Dissatisfied', 0);
$section->addListItem('3 - Neutral', 0);
$section->addListItem('4 - Satisfied', 0);
$section->addListItem('5 - Very satisfied', 0);
$section->addText('Would you like to receive our newsletter?');
$section->addListItem('☐ Yes, send me updates', 0);
$section->addListItem('☐ No thanks', 0);
$section->addText('What did you enjoy most? Please describe.');
$section->addText('Rate the venue (1 to 5)');
$section->addText('Link to your website or portfolio');

$phpWord->save($dir.'/conference-feedback.docx', 'Word2007');

// ---------------------------------------------------------------- .xlsx (A)
$spreadsheet = new Spreadsheet;
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Structured');

$sheet->fromArray([
    ['question', 'type', 'required', 'options', 'section'],
    ['Full Name', 'text', 'yes', '', 'Contact'],
    ['Email', 'email', 'yes', '', 'Contact'],
    ['Phone', 'phone', '', '', 'Contact'],
    ['Start date', 'date', 'yes', '', 'Contact'],
    ['Department', 'dropdown', '', 'Engineering, Design, Marketing, HR', 'Job Preferences'],
    ['Why do you want to join?', 'textarea', 'yes', '', 'Job Preferences'],
    ['Resume', 'file', 'yes', '', 'Job Preferences'],
    ['Preferred location', '', '', 'Bengaluru, Mumbai, Remote', 'Job Preferences'],
]);

(new Xlsx($spreadsheet))
    ->save($dir.'/registration-structured.xlsx');

// ---------------------------------------------------------------- .xlsx (B)
$spreadsheet = new Spreadsheet;
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Attendees');

$sheet->fromArray([
    ['Name', 'Email', 'Phone', 'Department', 'Years of experience', 'Feedback'],
    ['Rahul', 'rahul@example.com', '9999999999', 'Engineering', 5, 'Great platform'],
    ['Priya', 'priya@example.com', '8888888888', 'Design', 2, 'Loved the keynote'],
    ['Amit', 'amit@example.com', '7777777777', 'Sales', 8, 'Needs more sessions'],
]);

(new Xlsx($spreadsheet))
    ->save($dir.'/attendee-plain-header.xlsx');

echo "Wrote samples to {$dir}\n";
