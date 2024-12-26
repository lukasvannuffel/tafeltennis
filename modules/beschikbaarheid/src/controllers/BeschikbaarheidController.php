<?php
namespace modules;

use Craft;
use craft\web\Controller;

class BeschikbaarheidController extends Controller
{
    // Zorg ervoor dat de actie alleen beschikbaar is voor ingelogde gebruikers
    protected $allowAnonymous = false;

    public function actionIndex()
    {
        // Pad naar je CSV-bestand
        $csvFile = Craft::getAlias('@storage/csv/beschikbaarheid.csv');
        $csvData = [];

        if (file_exists($csvFile)) {
            $file = fopen($csvFile, 'r');
            $headers = fgetcsv($file);

            while ($row = fgetcsv($file)) {
                $csvData[] = array_combine($headers, $row);
            }
            fclose($file);
        }

        // Gegevens naar de template sturen
        return $this->renderTemplate('beschikbaarheid/index', [
            'csvData' => $csvData
        ]);
    }
}
