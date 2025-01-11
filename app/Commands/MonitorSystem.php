<?php

namespace App\Commands;

use App\Libraries\RedisClient;
use CodeIgniter\Cache\CacheInterface;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use React\EventLoop\Loop;


class MonitorSystem extends BaseCommand
{
    protected $group = 'Monitor';
    protected $name = 'app:monitor';
    protected $description = 'Displays monitor info';

    public function run(array $params)
    {
        $timer = Loop::addPeriodicTimer(1.0, function () {
            $redisClient = new RedisClient();
            $coasters = $redisClient->getCoasters();
            CLI::clearScreen();
            if(count($coasters) > 0) {
                foreach ($coasters as $key => $value) {

                    if (array_key_exists("wagons", $value)) {
                        $ilosc_wagonow = count($value['wagons']);
                    } else {
                        $ilosc_wagonow = 0;
                    }
                    //$value['liczba_klientow'] = 120;
                    $czas_pracy_od = $value['godziny_od'];
                    $czas_pracy_do = $value['godziny_do'];

                    $brakuje_wagonow = null;


                    $pracownik_wymagany = 1;
                    $pracownik_per_wagon = 2;
                    $status = "";

                    $s = $value['dl_trasy'];
                    if (array_key_exists('wagons', $value)) {
                        //czas pracy całej kolejki
                        $czas_pracy_minuty =
                            (strtotime($czas_pracy_do) - strtotime($czas_pracy_od)) / 60;

                        $sum_cycles = 0;
                        $sum_place = 0;
                        foreach ($value['wagons'] as $wagon) {
                            //obliczamy sumę pracowników wymaganych
                            $pracownik_wymagany += $pracownik_per_wagon;
                            $sum_place += $wagon['ilosc_miejsc'];
                            $v_wagonu = $wagon['predkosc_wagonu'];
                            //w jakim czasie wagon pokona całą trasę, przyjmujemy że dl_trasy to całość trasy w górę i w dół
                            //dodajemy 5 minut przerwy
                            $t_cykl = (($s / $v_wagonu) / 60) + 5;
                            $sum_cycles += $t_cykl;
                        }
                        //ustalenie ilości cykli jakie można przejechać w ciągu czasu pracy
                        $day_cycles = $czas_pracy_minuty / $sum_cycles;
                        //ustalenie ile osób można zabrać
                        $sum_people_day = $day_cycles * $sum_place;
                        $people_max = $day_cycles * $value['liczba_klientow'];


                        if ($sum_people_day < $value['liczba_klientow']) {
                            $diff = $value['liczba_klientow'] - $sum_people_day;
                            $status .= "brakuje miejsc dla $diff klientów, ";
                            $brakuje_wagonow = ceil($diff / $sum_people_day);
                            if ($brakuje_wagonow > 0) {
                                $status .= "brakuje $brakuje_wagonow wagonów, ";
                            }
                            if ($value['liczba_personelu'] < $pracownik_wymagany) {
                                $diff = $pracownik_wymagany - $value['liczba_personelu'];
                                $status .= "brakuje pracowników $diff, ";
                            } else if ($value['liczba_personelu'] > $pracownik_wymagany) {
                                $without_extra = $pracownik_wymagany;
                                $pracownik_wymagany -= $brakuje_wagonow * $pracownik_per_wagon;
                                $pracownik_wymagany = abs($pracownik_wymagany);
                                $status .= " nadmiar pracowników $pracownik_wymagany (wraz z dodatkowym wagonem), bez dodatkowego wagonu: $without_extra  ";
                            }
                        }
                        if ($people_max < $sum_people_day) {

                            $wagony_need = ceil($people_max / ($sum_place * $day_cycles));
                            $status .= "potrzeba wagonów $wagony_need";
                        }

                        $should_be = abs($ilosc_wagonow + $brakuje_wagonow);
                        $pracownik_wymagany = abs($pracownik_wymagany);
                        if ($value['liczba_klientow'] * 2 < $sum_people_day) {
                            $status .= "kolejka obsłuży dwukrotnie więcej osób, ";

                        }

                        if ($status == "") {
                            $status = "OK";
                        } else {
                            log_message('notice', "[Kolejka A$key] - Problem: $status");
                        }

                    } else {
                        $sum_people_day = "X";
                        $wagon_required = "X";
                        $status = "Błąd: Brak Wagonów ";
                        $should_be = "X";
                        log_message('error', "[Kolejka A$key] - Brak wagonów");
                    }

                    CLI::clearScreen();
                    CLI::newLine();
                    CLI::write("[Kolejka A$key]");
                    CLI::write("Godziny działania: {$value['godziny_od']} - {$value['godziny_do']}");
                    CLI::write("Liczba wagonów: $ilosc_wagonow/$should_be");
                    CLI::write("Dostępny personel: {$value['liczba_personelu']}/$pracownik_wymagany");
                    CLI::write("Klienci dziennie/max obłożenie: {$value['liczba_klientow']}/$sum_people_day");
                    CLI::write("$status");
                }
            }else{
                CLI::clearScreen();
                CLI::newLine();
                CLI::write("Brak kolejek");
                CLI::newLine();
            }


        });


    }

}
