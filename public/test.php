
<?php

require __DIR__.'/home/u608187177/domains/turamunicipalboard.com/public_html/laravel_turamunicipal/bootstrap/autoload.php';
$app = require_once __DIR__.'/home/u608187177/domains/turamunicipalboard.com/public_html/laravel_turamunicipal/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use Illuminate\Support\Facades\Artisan;

// Define the commands
$commands = [
    'config:clear',
    'config:cache',
];

// Function to run Artisan commands
function runArtisanCommands($commands) {
    foreach ($commands as $command) {
        Artisan::call($command);
        echo "Output of $command:\n";
        echo Artisan::output();
    }
}

// Run the commands
runArtisanCommands($commands);

echo "All Artisan commands executed.\n";

