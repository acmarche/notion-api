<?php

namespace AcMarche\Notion\Lib;

use Carbon\Carbon;
use Carbon\CarbonInterface;

class Grr
{
    private ?\PDO $pdo = null;
    public static array $rooms = [
        113 => 'La Box',
        114 => 'La Créative',
        115 => 'Meeting Room',
        116 => 'Relax Room',
        117 => 'La Digital Room',
        135 => 'L\'Arène',
        136 => 'Talentum',
        137 => 'Cusine',
        138 => 'L\'Inspirante',
    ];

    public function connect()
    {
        $this->pdo = new \PDO('mysql:host=localhost;dbname=grr_ville', $_ENV['GRR_DB_USER'], $_ENV['GRR_DB_PASSWORD']);
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    public function findByRoomId(int $roomId): array
    {
        if (!$roomId) {
            $ids = array_keys(Grr::$rooms);
        } else {
            $ids = [$roomId];
        }

        $sql = "SELECT id,from_unixtime(start_time, '%Y-%m-%d') as dayStart,from_unixtime(end_time, '%Y-%m-%d') as dayEnd,from_unixtime(start_time, '%H:%i') as dayStartHours,from_unixtime(end_time, '%H:%i') as dayEndHours,start_time,end_time,entry_type,repeat_id,room_id,timestamp,type,statut_entry,moderate,supprimer 
FROM grr_entry 
WHERE room_id IN (".implode(",", $ids).") AND (moderate = :approuved OR moderate = 2) AND supprimer = :del";

        $statement = $this->pdo->prepare($sql);
        $toApprouved = $del = 0;
        $statement->bindParam(':approuved', $toApprouved, \PDO::PARAM_INT);
        $statement->bindParam(':del', $del, \PDO::PARAM_INT);

        try {
            $statement->execute();

            return $statement->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $exception) {
            Mailer::sendError("grr esquare findByRoomId ".$exception->getMessage());
            throw new \Exception(
                json_encode($data).' '.json_encode($statement->errorInfo()).' '.$exception->getMessage(),
            );
        }
    }

    /**
     * @throws \Exception
     */
    public function treatment(array $data): array
    {
        $person = $data['person'];
        $hours = $data['person']['hours'];
        $days = $data['daysSelected'];
        $roomId = $data['roomId'];
        $fieldsRequired = ['name', 'email', 'phone', 'number_people', 'street'];

        if (count($days) === 0) {
            throw new \Exception('Aucun jour sélectionné');
        }
        if (!$hours) {
            throw new \Exception('Aucune heure sélectionnée');
        }
        if (!$roomId) {
            throw new \Exception('Aucune salle sélectionnée');
        }
        foreach ($fieldsRequired as $field) {
            if (!array_key_exists($field, $person) || $person[$field] == '') {
                throw new \Exception('Veuillez remplir tous les champs '.$field);
            }
        }

        $this->connect();
        foreach ($days as $day) {
            $dates = $this->getDateBeginAndDateEnd($day, $hours);
            try {
                $results = $this->checkFree($roomId, $dates[0], $dates[1]);
                if (count($results) > 0) {
                    throw new \Exception('Déjà réservé à la date du '.$day.' '.$hours);
                }
            } catch (\Exception $exception) {
                throw new \Exception($exception->getMessage());
            }
        }

        return ['ok'];
        $user = [
            ':login' => $person->email,
            ':nom' => substr($person->name, 0, 30),
            ':prenom' => substr($person->societe, 0, 30),
            ':password' => self::generatePassword(),
            ':email' => $person->email,
            ':statut' => 'visiteur',
            ':etat' => 'actif',
            ':default_area' => 23,
            ':default_style' => 'default',
            ':default_list_type' => 'select',
            ':source' => 'local',
        ];

        try {
            $this->insertUser($user, $person->email);
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }

        $description = 'Société '.$person->vat.' '.'Tva '.$person->vat.' '.'Nbre personne: '.$person->number_people.' Disposition table: '.$person->table_layout.' Autre information'.$person->info;
        foreach ($days as $day) {
            $dates = $this->getDateBeginAndDateEnd($day, $hours);
            $item = [
                ':start_time' => $dates[0]->getTimestamp(),
                ':end_time' => $dates[1]->getTimestamp(),
                ':entry_type' => 0,
                ':type' => 'H',//location
                ':repeat_id' => 0,
                ':room_id' => $roomId,
                ':timestamp' => null,
                ':create_by' => 'ESQUARE',
                ':beneficiaire_ext' => '',
                ':beneficiaire' => $person->email,
                ':name' => substr($person->name, 0, 80),
                ':description' => $description,
                ':statut_entry' => '-',
                ':moderate' => 1,
                ':option_reservation' => -1,
            ];

            try {
                $this->insertEntry($item);
            } catch (\Exception $exception) {
                throw new \Exception($exception->getMessage());
            }
        }

        return ['ok'];
    }

    /**
     * @param string $day 2024-04-27
     * @param string $hours 9-17
     * @return array|[<int,CarbonInterface>,<int,CarbonInterface>]
     */
    private function getDateBeginAndDateEnd(string $day, string $hours): array
    {
        $startTime = Carbon::createFromFormat('Y-m-d', $day, new \DateTimeZone('Europe/Paris'));
        $endTime = Carbon::createFromFormat('Y-m-d', $day, new \DateTimeZone('Europe/Paris'));
        [$hourBegin, $hourEnd] = explode('-', $hours);

        $startTime->hour = (int)$hourBegin;
        $startTime->minute = 0;
        $startTime->second = 0;
        $endTime->hour = (int)$hourEnd;
        $endTime->minute = 0;
        $endTime->second = 0;

        return [$startTime, $endTime];
    }

    /**
     * @throws \Exception
     */
    private function insertEntry(array $data): void
    {
        $sql = 'INSERT INTO grr_entry(`start_time`,`end_time`,`entry_type`,`repeat_id`,`room_id`,`timestamp`,`create_by`,`beneficiaire_ext`,`beneficiaire`,`name`,`type`,`description`,`statut_entry`,`option_reservation`,`moderate`)
VALUES(:start_time,:end_time,:entry_type,:repeat_id,:room_id,:timestamp,:create_by,:beneficiaire_ext,:beneficiaire,:name,:type,:description,:statut_entry,:option_reservation,:moderate)';

        $statement = $this->pdo->prepare($sql);

        try {
            $statement->execute($data);
        } catch (\Exception $exception) {
            Mailer::sendError("grr esquare error insert entry ".$exception->getMessage());
            throw new \Exception(
                json_encode($data).' '.json_encode($statement->errorInfo()).' '.$exception->getMessage(),
            );
        }
    }

    /**
     * @throws \Exception
     */
    private function insertUser(array $user, string $email): void
    {
        if ($this->checkUser($email)) {
            return;
        }

        $sql = 'INSERT INTO grr_utilisateurs(`login`,`nom`,`prenom`,`password`,`email`,`statut`,`etat`,`default_area`,`default_style`,`default_list_type`,`source`)
VALUES(:login,:nom,:prenom,:password,:email,:statut,:etat,:default_area,:default_style,:default_list_type,:source)';

        $statement = $this->pdo->prepare($sql);

        try {
            $statement->execute($user);
        } catch (\Exception $exception) {
            Mailer::sendError("grr esquare error insert user ".$exception->getMessage());
            throw new \Exception(
                json_encode($user).' '.json_encode($statement->errorInfo()).' '.$exception->getMessage(),
            );
        }
    }

    /**
     * @throws \Exception
     */
    private function checkUser(string $email): bool
    {
        $sql = 'SELECT * FROM grr_utilisateurs WHERE `email` =:email';

        $statement = $this->pdo->prepare($sql);

        try {
            $statement->execute(['email' => $email]);
            $result = $statement->fetchAll();
            if (count($result) > 0) {
                return true;
            }
        } catch (\Exception $exception) {
            Mailer::sendError("grr esquare check user ".$exception->getMessage());
            throw new \Exception(
                json_encode($user).' '.json_encode($statement->errorInfo()).' '.$exception->getMessage(),
            );
        }

        return false;
    }

    /**
     * @throws \Exception
     */
    private function checkFree(int $roomId, CarbonInterface $startTime, CarbonInterface $endTime): false|array
    {
        $startModif = clone $startTime;
        $startModif->modify('+1 second');
        $endModif = clone $endTime;
        $endModif->modify('-1 second');

        $sql = "SELECT * FROM grr_entry WHERE (`start_time` BETWEEN :start AND :end OR `end_time` BETWEEN :start AND :end) AND `room_id` = :room_id";
        $stmt = $this->pdo->prepare($sql);
        try {
            $stmt->execute([
                ':start' => $startTime->getTimestamp(),
                ':end' => $endTime->getTimestamp(),
                ':room_id' => $roomId,
            ]);
            Mailer::sendError("grr esquare check free ".$stmt->queryString);

            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $exception) {
            Mailer::sendError("grr esquare check free ".$exception->getMessage());
            throw new \Exception(
                json_encode($stmt->errorInfo()).' '.$exception->getMessage(),
            );
        }
    }

    private static function generatePassword(): string
    {
        return md5(random_bytes(7));
    }

}