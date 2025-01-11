<?php

namespace App\Controllers;

use App\Libraries\RedisClient;

class Coaster extends BaseController
{

    private RedisClient $redisClient;

    public function __construct()
    {
        $this->redisClient = new RedisClient();
    }

    public function index()
    {

        $coasters = $this->redisClient->getCoasters();

        return $this->response->setJSON([
            'message' => 'Pomyślnie pobrano',
            'coasters_count' => $this->redisClient->getCoastersCount(),
            'coasters' => $coasters,
        ])->setStatusCode(200);
    }

    public function store()
    {
        $body = $this->request->getJSON(true);

        $allowedKeys = [
            'liczba_personelu', 'liczba_klientow', 'dl_trasy', 'godziny_od', 'godziny_do'
        ];

        //domyslne wartości kolejki nowej kolejki, wykorzystywane kiedy nie został podany jakiś argument
        $default_queue = '{"liczba_personelu": 16, "liczba_klientow": 60000, "dl_trasy": 1800, "godziny_od": "8:00", "godziny_do":"16:00"}';
        $default_data = json_decode($default_queue, true);
        if(!empty($body)) {
            foreach ($body as $key => &$value) {
                if (in_array($key, $allowedKeys)) {
                    if (empty($value)) {
                        $value = $default_data[$key];
                    }
                } else {
                    unset($body[$key]);
                }
            }
            //jezeli nie wszystkie zostały wypełnione wypełniane defaultowymi.
            foreach($default_data as $key => $val) {

                if(!array_key_exists($key, $body)) {
                    $body[$key] = $val;
                }
            }
        }else{
            //wartosci domyslne.
            $body = $default_data;
        }
        //pobranie ilości kolejek
        $coasters_count = $this->redisClient->getCoastersCount();
        $coasters = $this->redisClient->getCoasters();
        if (!$coasters_count && !is_int($coasters_count)) {
            $coasters_count = 1;
        } else {
            $coasters_count++;
        }

        $coasters[$coasters_count] = $body;

        $this->redisClient->setCoasters($coasters);
        $this->redisClient->setCoastersCount($coasters_count);
        return $this->response->setJSON([
            'message' => 'Pomyślnie dodano kolejkę',
            'coasters_count' => $this->redisClient->getCoastersCount(),
            'coasters' => $this->redisClient->getCoasters(),
        ])->setStatusCode(201);
    }

    public function wagonStore($coasterId)
    {

        $coasters = $this->redisClient->getCoasters();

        /**
         * Sprawdzenie czy kolejka istnieje.
         */
        if (!array_key_exists($coasterId, $coasters)) {
            return $this->response->setJSON([
                'message' => 'Taka kolejka górska nie istnieje'
            ])->setStatusCode(422);
        }

        $body = $this->request->getJSON(true);
        $allowedKeys = [
            'ilosc_miejsc', 'predkosc_wagonu'
        ];
        //domyslne wartości wagonu kolejki, wykorzystywane kiedy nie został podany jakiś argument
        $default_data = ['ilosc_miejsc' => 32, 'predkosc_wagonu' => 1.2];
        if(!empty($body)) {
            foreach ($body as $key => &$value) {
                if (in_array($key, $allowedKeys)) {
                    if (empty($value)) {
                        $value = $default_data[$key];
                    }
                } else {
                    unset($body[$key]);
                }
            }
            //jezeli nie wszystkie zostały wypełnione wypełniane defaultowymi.
            foreach($default_data as $key => $val) {

                if(!array_key_exists($key, $body)) {
                    $body[$key] = $val;
                }
            }
        }else{
            //wartosci domyslne.
            $body = $default_data;
        }

        //jezeli kolejka istnieje dodajemy wagon.
        if (!array_key_exists('wagons', $coasters[$coasterId])) {
            //jeżeli nie istnieje tworzymy i dodajemy nowy wagon
            $coasters[$coasterId]['wagons']["0"] = $body;
        } else {
            $wagonCount = count($coasters[$coasterId]['wagons']);
            $wagonCount++;
            //dodajemy nowy wagon
            $coasters[$coasterId]['wagons']["$wagonCount"] = $body;

        }
        $this->redisClient->setCoasters($coasters);

        return $this->response->setJSON([
            'message' => "Pomyślnie dodano wagon do kolejki ID $coasterId",
            'coasters_count' => $this->redisClient->getCoastersCount(),
            'coasters' => $this->redisClient->getCoasters(),
        ])->setStatusCode(201);

    }


    public function wagonDestroy($coasterId, $wagonId)
    {
        $coasters = $this->redisClient->getCoasters();
        /**
         * Sprawdzenie czy kolejka istnieje.
         */
        if (!array_key_exists($coasterId, $coasters) ||
            !array_key_exists('wagons', $coasters[$coasterId]) ||
            !array_key_exists("$wagonId", $coasters[$coasterId]['wagons'])
        ) {
            return $this->response->setJSON([
                'message' => 'Taka kolejka górska bądź wagon nie istnieje',
                'wagony' => $coasters[$coasterId]['wagons']
            ])->setStatusCode(422);
        }

        unset($coasters[$coasterId]['wagons'][$wagonId]);
        $this->redisClient->setCoasters($coasters);

        return $this->response->setJSON([
            'message' => "Pomyślnie usunięto wagon $wagonId z kolejki $coasterId",
            'coasters_count' => $this->redisClient->getCoastersCount(),
            'coasters' => $this->redisClient->getCoasters(),
        ])->setStatusCode(200);
    }

    public function coasterUpdate($coasterId)
    {
        $coasters = $this->redisClient->getCoasters();

        /**
         * Sprawdzenie czy kolejka istnieje.
         */
        if (!array_key_exists($coasterId, $coasters)) {
            return $this->response->setJSON([
                'message' => 'Taka kolejka górska bądź wagon nie istnieje'
            ])->setStatusCode(422);
        }

        //sprawdzenie czy jest coś do aktualizacji, zakładamy, że pole musi mieć jakąś wartość, a niewymaganym jest aby wszystkie miały wartość

        $body = $this->request->getJSON(true);
        $allowedKeys = ['liczba_personelu', 'liczba_klientow', 'godziny_od', 'godziny_do'];

        $filtered_data = [];
        if(!empty($body)) {
            foreach ($body as $key => &$value) {
                if (in_array($key, $allowedKeys)) {
                    if (!empty($value)) {
                        $filtered_data[$key] = $value;
                    }
                }
            }
        }
        if (empty($filtered_data)) {
            return $this->response->setJSON([
                'message' => 'Brak danych do aktualizacji'
            ])->setStatusCode(422);
        }

        foreach ($filtered_data as $filter_key => $filter_value) {
            //update
            if (is_numeric($filter_value)) {
                $filter_value = (int)$filter_value;
            }
            $coasters[$coasterId][$filter_key] = $filter_value;
        }
        $this->redisClient->setCoasters($coasters);

        return $this->response->setJSON([
            'message' => "Pomyślnie zaktualizowano kolejkę $coasterId",
            'coasters_count' => $this->redisClient->getCoastersCount(),
            'coasters' => $this->redisClient->getCoasters(),
        ])->setStatusCode(200);
    }

    public function reset(){
        $this->redisClient->reset();
        return $this->response->setJSON([
            'message' => 'Pomyślnie zresetowano system.',
        ])->setStatusCode(200);
    }

}
