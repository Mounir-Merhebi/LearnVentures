<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Schema;

echo "Lessons table columns:\n";
$columns = Schema::getColumnListing('lessons');
foreach ($columns as $column) {
    echo "  - $column\n";
}

echo "\nChecking if concept_slugs column exists: " . (Schema::hasColumn('lessons', 'concept_slugs') ? 'YES' : 'NO') . "\n";
echo "Checking if concept_slug column exists: " . (Schema::hasColumn('lessons', 'concept_slug') ? 'YES' : 'NO') . "\n";

