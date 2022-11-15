<?php

namespace App\Services;

use Google\Client;
use Google\Exception;
use Google\Service\Drive;
use Google\Service\Sheets;
use Google_Service_Sheets_Spreadsheet;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use function Sodium\add;

class SheetsServices
{
    public Client $client;

    public Sheets $service;

    /**
     * @var Sheets\Spreadsheet
     */
    public $spreadSheet;

    public string $sheetID;

    public const MONTH = [
        1  => 'OCAK',
        2  => 'ŞUBAT',
        3  => 'MART',
        4  => 'NİSAN',
        5  => 'MAYIS',
        6  => 'HAZİRAN',
        7  => 'TEMMUZ',
        8  => 'AĞUSTOS',
        9  => 'EYLÜL',
        10 => 'EKİM',
        11 => 'KASIM',
        12 => 'ARALIK',
    ];

    public function __construct()
    {
        $this->client  = $this->getClient();
        $this->service = new Sheets($this->client);
        $this->sheetID = '1ImWU12b11-zlgRQ4Ex8HH-2PzH1dTeT_08ZPg50iAnc';
    }

    /**
     * @throws Exception
     */
    public function getClient(): Client
    {
        $client = new Client();
        $client->setApplicationName('Google Sheets API PHP Quickstart');
        // $client->useApplicationDefaultCredentials();
        $client->setScopes([\Google_Service_Sheets::SPREADSHEETS]);
        // $client->setScopes('https://www.googleapis.com/auth/spreadsheets');
        // $client->setRedirectUri(route('dashboard'));
        $client->setAccessType('offline');
        $client->setAuthConfig(storage_path('credentials.json'));
        // $client->setPrompt('select_account consent');

        return $client;
    }

    public function checkExistUserSheet()
    {

    }

    public function readSheet()
    {
        $sheet = $this->service->spreadsheets->get($this->sheetID);

        // Veri Oku
        $range    = 'Sayfa1';
        $response = $this->service->spreadsheets_values->get($this->sheetID, $range);
        $values   = $response->getValues();

        // Satır Yaz
        $setValues = [['Satır:' . count($values) + 1, now()->format('d.m.Y')]];
        $body      = new \Google_Service_Sheets_ValueRange();
        $body->setValues($setValues);
        $options = ['valueInputOption' => 'USER_ENTERED'];

        $result = $this->service->spreadsheets_values->append($this->sheetID, $range, $body, $options);


        dump($values, $result->getUpdates());
    }

    /**
     * @param string $title
     * @return bool
     */
    public function createPage(string $title): bool
    {
        $bodySheet = new \Google_Service_Sheets_BatchUpdateSpreadsheetRequest();
        $bodySheet->setRequests([
            'addSheet' => [
                'properties' => [
                    'title' => $title,
                ]
            ]
        ]);

        try {
            $response = $this->service->spreadsheets->batchUpdate($this->sheetID, $bodySheet);
            $this->getSpreedSheetInfo(true);

            return true;
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());

            return false;
        }
    }

    public function createMonthTemplate(Carbon $date): void
    {
        $monthName = self::MONTH[$date->month];

        // SheetID değerini bul
        $this->getSpreedSheetInfo();
        $sheets      = collect($this->spreadSheet->getSheets());
        $activeSheet = $sheets->firstWhere('properties.title', '=', $date->year);
        $sheetId     = $activeSheet->properties->sheetId;

        // Sheet içerisine yazılacak veriler
        $payload = [
            [$monthName],
            ['Tarih', 'TL Maaş', 'USD Maaş', 'EUR Maaş', 'GBR Maaş', 'USD Kur', 'EUR Kur', 'GBR Kur',]
        ];

        if ($date->month > 1) {
            $payload = array_merge([['']], $payload);
        }

        $body = new Sheets\ValueRange();
        $body->setValues($payload);

        $options = ['valueInputOption' => 'USER_ENTERED'];

        $result = $this->service
            ->spreadsheets_values
            ->append($this->sheetID, $date->year, $body, $options);

        $addedHeaderRow = explode('!', $result->getUpdates()->getUpdatedRange());
        $addedHeaderRow = explode(':', $addedHeaderRow[1]);
        $addedHeaderRow = (int) preg_replace("/[^0-9]/", "", $addedHeaderRow[0]);


        if ($date->month > 1) {
            $addedHeaderRow += 1;
        }

        $request = new Sheets\BatchUpdateSpreadsheetRequest();
        $request->setRequests([
            'mergeCells' => [
                'range'     => [
                    'sheetId'          => $sheetId,
                    'startRowIndex'    => $addedHeaderRow - 1,
                    'endRowIndex'      => $addedHeaderRow,
                    'startColumnIndex' => 0,
                    'endColumnIndex'   => 8,
                ],
                'mergeType' => 'MERGE_ROWS',
            ],
        ]);
        $this->service->spreadsheets->batchUpdate($this->sheetID, $request);

        $request->setRequests([
            'repeatCell' => [
                'range'  => [
                    'sheetId'          => $sheetId,
                    'startRowIndex'    => $addedHeaderRow - 1,
                    'endRowIndex'      => $addedHeaderRow,
                    'startColumnIndex' => 0,
                    'endColumnIndex'   => 7,
                ],
                "cell"   => [
                    "userEnteredFormat" => [
                        "backgroundColor"     => [
                            "red"   => 0.4,
                            "green" => 0.4,
                            "blue"  => 0.4
                        ],
                        "horizontalAlignment" => "CENTER",
                        "textFormat"          => [
                            "foregroundColor" => [
                                "red"   => 1.0,
                                "green" => 1.0,
                                "blue"  => 1.0
                            ],
                            "fontSize"        => 12,
                            "bold"            => true
                        ]
                    ]
                ],
                "fields" => "userEnteredFormat(backgroundColor,textFormat,horizontalAlignment)"
            ],
        ]);
        $this->service->spreadsheets->batchUpdate($this->sheetID, $request);
    }

    public function addDayInfo($payload): void
    {
        // Document ID setle
        $this->sheetID = $payload['document_id'];

        // Tarihi parse et
        $date = Carbon::parse($payload['date']);

        $this->getSpreedSheetInfo();
        $sheets      = collect($this->spreadSheet->getSheets());
        $activeSheet = $sheets->firstWhere('properties.title', '=', $date->year);

        // Sheet yoksa yeni ekle
        if (!$activeSheet) {
            $this->createPage($date->year);
            $this->createMonthTemplate($date);
        } elseif ($date->day === 1) {
            // Ayın ilk günü ise yeni ay başlığı oluştur
            $this->createMonthTemplate($date);
        }

        $setValues = [
            [
                $date->format('d.m.Y'),
                $payload['salary'],
                $payload['list']['USD']['price'],
                $payload['list']['EUR']['price'],
                $payload['list']['GBP']['price'],
                $payload['list']['USD']['currency'],
                $payload['list']['EUR']['currency'],
                $payload['list']['GBP']['currency'],
            ]
        ];

        $body = new \Google_Service_Sheets_ValueRange();
        $body->setValues($setValues);

        $options = ['valueInputOption' => 'USER_ENTERED'];

        $this->service
            ->spreadsheets_values
            ->append($this->sheetID, $date->year, $body, $options);
    }

    public function getSpreedSheetInfo($refresh = false): Sheets\Spreadsheet
    {
        if ($this->spreadSheet && $refresh === false) {
            return $this->spreadSheet;
        }

        $this->spreadSheet = $this->service->spreadsheets->get($this->sheetID);

        return $this->spreadSheet;
    }
}
